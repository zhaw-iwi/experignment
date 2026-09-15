import { spawn } from "node:child_process";
import { createServer } from "node:net";
import { mkdtemp, mkdir, rm, writeFile } from "node:fs/promises";
import { tmpdir } from "node:os";
import path from "node:path";
import { createRequire } from "node:module";
import { fileURLToPath } from "node:url";

const require = createRequire(import.meta.url);
const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "../..");
const runtimeDirectory = await mkdtemp(path.join(tmpdir(), "experignment-browser-"));
const databasePath = path.join(runtimeDirectory, "fixture.sqlite");
const environmentPath = path.join(runtimeDirectory, "browser.env");
const sessionDirectory = path.join(runtimeDirectory, "sessions");
const externalArtifactDirectory = process.env.POINTS_TEST_ARTIFACT_DIR || "";
const artifactDirectory = externalArtifactDirectory || path.join(runtimeDirectory, "artifacts");
let serverProcess = null;
let serverStderr = "";

function runProcess(command, args, options = {}) {
    return new Promise((resolve, reject) => {
        const child = spawn(command, args, {
            cwd: projectRoot,
            stdio: options.stdio || ["ignore", "pipe", "pipe"],
            env: options.env || process.env,
            windowsHide: true,
        });
        let stdout = "";
        let stderr = "";
        if (child.stdout) {
            child.stdout.on("data", (chunk) => {
                stdout += String(chunk);
            });
        }
        if (child.stderr) {
            child.stderr.on("data", (chunk) => {
                stderr += String(chunk);
            });
        }
        child.on("error", reject);
        child.on("exit", (code) => resolve({ code: code ?? 1, stdout, stderr }));
    });
}

async function availablePort() {
    const socket = createServer();
    await new Promise((resolve, reject) => {
        socket.once("error", reject);
        socket.listen(0, "127.0.0.1", resolve);
    });
    const address = socket.address();
    const port = typeof address === "object" && address ? address.port : 0;
    await new Promise((resolve) => socket.close(resolve));
    if (!port) {
        throw new Error("Could not reserve a browser-test port.");
    }
    return port;
}

function isolatedServerEnvironment() {
    const environment = { ...process.env };
    const exactKeys = [
        "ADMIN_ACCESS_CODE_HASH",
        "APP_SESSION_NAME",
        "APP_SESSION_SECURE",
        "APP_TIMEZONE",
        "EXPERIMENT_ENV_FILE",
        "PREFLIGHT_ENABLED",
    ];
    for (const key of Object.keys(environment)) {
        if (key.startsWith("EXPERIMENT_DB_") || exactKeys.includes(key)) {
            delete environment[key];
        }
    }
    environment.EXPERIMENT_ENV_FILE = environmentPath;
    return environment;
}

async function waitForServer(baseUrl) {
    for (let attempt = 0; attempt < 50; attempt += 1) {
        try {
            const response = await fetch(`${baseUrl}/api/bootstrap.php`);
            if (response.ok) {
                return;
            }
        } catch (error) {
            // The server may still be starting.
        }
        await new Promise((resolve) => setTimeout(resolve, 100));
    }
    throw new Error("The isolated PHP browser-test server did not start.");
}

try {
    await mkdir(artifactDirectory, { recursive: true });
    await mkdir(sessionDirectory, { recursive: true });
    const fixtureResult = await runProcess(
        process.env.PHP_BINARY || "php",
        [path.join(projectRoot, "tests/browser/create_fixture.php"), databasePath]
    );
    if (fixtureResult.code !== 0) {
        throw new Error(fixtureResult.stderr || "Could not create the browser fixture database.");
    }

    const sqliteDsn = `sqlite:${databasePath.replaceAll("\\", "/")}`;
    await writeFile(
        environmentPath,
        [
            `EXPERIMENT_DB_DSN='${sqliteDsn}'`,
            "APP_SESSION_NAME='experignment_browser_test'",
            "APP_SESSION_SECURE=false",
            "APP_TIMEZONE='Europe/Zurich'",
            "PREFLIGHT_ENABLED=false",
            "",
        ].join("\n"),
        "utf8"
    );

    const port = await availablePort();
    const baseUrl = `http://127.0.0.1:${port}`;
    serverProcess = spawn(
        process.env.PHP_BINARY || "php",
        ["-d", `session.save_path=${sessionDirectory}`, "-S", `127.0.0.1:${port}`, "-t", projectRoot],
        {
            cwd: projectRoot,
            env: isolatedServerEnvironment(),
            stdio: ["ignore", "ignore", "pipe"],
            windowsHide: true,
        }
    );
    serverProcess.stderr.on("data", (chunk) => {
        serverStderr = `${serverStderr}${String(chunk)}`.slice(-8_000);
    });
    await waitForServer(baseUrl);

    const playwrightEnvironment = {
        ...process.env,
        POINTS_TEST_BASE_URL: baseUrl,
        POINTS_TEST_ARTIFACT_DIR: artifactDirectory,
    };
    const playwrightResult = await runProcess(
        process.execPath,
        [
            require.resolve("@playwright/test/cli"),
            "test",
            "--config",
            path.join(projectRoot, "tests/browser/playwright.config.cjs"),
        ],
        { env: playwrightEnvironment, stdio: "inherit" }
    );
    if (playwrightResult.code !== 0) {
        if (serverStderr !== "") {
            process.stderr.write(`\nPHP browser-test server output:\n${serverStderr}\n`);
        }
        process.exitCode = playwrightResult.code;
    } else if (process.env.POINTS_TEST_INSPECT === "1") {
        process.stdout.write(`inspection_url=${baseUrl}\n`);
        await new Promise((resolve) => {
            process.once("SIGINT", resolve);
            process.once("SIGTERM", resolve);
        });
    }
} finally {
    if (serverProcess && serverProcess.exitCode === null) {
        serverProcess.kill();
    }
    await rm(runtimeDirectory, { recursive: true, force: true });
}
