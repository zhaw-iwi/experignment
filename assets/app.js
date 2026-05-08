const state = {
    assignment: null,
    emailValidationActive: false,
};

const STORED_STUDENT_EMAIL_KEY = "experiment_student_email";

const dom = {
    entryCard: document.getElementById("entryCard"),
    loadingState: document.getElementById("loadingState"),
    entryState: document.getElementById("entryState"),
    claimForm: document.getElementById("claimForm"),
    studentEmail: document.getElementById("studentEmail"),
    tabletPromptButton: document.getElementById("tabletPromptButton"),
    tabletConfirmButton: document.getElementById("tabletConfirmButton"),
    assignmentState: document.getElementById("assignmentState"),
    assignmentVariant: document.getElementById("assignmentVariant"),
    assignmentStatus: document.getElementById("assignmentStatus"),
    pinBadge: document.getElementById("pinBadge"),
    surveyLink: document.getElementById("surveyLink"),
    surveyCopyButton: document.getElementById("surveyCopyButton"),
    agentActionGroup: document.getElementById("agentActionGroup"),
    agentLink: document.getElementById("agentLink"),
    agentCopyButton: document.getElementById("agentCopyButton"),
    assignmentHint: document.getElementById("assignmentHint"),
    assignmentMessageArea: document.getElementById("assignmentMessageArea"),
    messageArea: document.getElementById("messageArea"),
};

const tabletModalElement = document.getElementById("tabletConfirmModal");
const tabletModal = tabletModalElement ? bootstrap.Modal.getOrCreateInstance(tabletModalElement) : null;

document.addEventListener("DOMContentLoaded", () => {
    bootstrapExistingAssignment();

    if (dom.claimForm) {
        dom.claimForm.addEventListener("submit", (event) => {
            event.preventDefault();
            claimAssignment("text");
        });
    }

    if (dom.studentEmail) {
        dom.studentEmail.addEventListener("input", onStudentEmailInput);
    }

    if (dom.tabletPromptButton) {
        dom.tabletPromptButton.addEventListener("click", onTabletPrompt);
    }

    if (dom.tabletConfirmButton) {
        dom.tabletConfirmButton.addEventListener("click", () => {
            if (tabletModal) {
                tabletModal.hide();
            }
            claimAssignment("tablet", true);
        });
    }

    if (dom.surveyCopyButton) {
        dom.surveyCopyButton.addEventListener("click", () => {
            copyLink(dom.surveyLink ? (dom.surveyLink.href || "") : "");
        });
    }

    if (dom.agentCopyButton) {
        dom.agentCopyButton.addEventListener("click", () => {
            copyLink(dom.agentLink ? (dom.agentLink.href || "") : "");
        });
    }
});

async function bootstrapExistingAssignment() {
    try {
        const payload = await apiRequest("api/bootstrap.php", { method: "GET" });
        if (payload.assignment) {
            renderAssignment(payload.assignment, true, getStoredStudentEmail());
            return;
        }
    } catch (error) {
        showMessage(error.message || "Die bestehende Zuweisung konnte nicht geprüft werden.", "danger");
    } finally {
        if (dom.loadingState) {
            dom.loadingState.classList.add("d-none");
        }
        if (!state.assignment && dom.entryState) {
            dom.entryState.classList.remove("d-none");
        }
    }
}

function onTabletPrompt() {
    clearMessage();
    state.emailValidationActive = true;
    if (!validateEmailField()) {
        showValidationMessage("Bitte geben Sie vorab Ihre gültige Studierenden-E-Mail-Adresse ein.", "warning");
        return;
    }
    state.emailValidationActive = false;
    if (tabletModal) {
        tabletModal.show();
    }
}

async function claimAssignment(pool, tabletConfirmed = false) {
    clearMessage();
    state.emailValidationActive = true;
    if (!validateEmailField()) {
        showValidationMessage("Bitte geben Sie eine gültige E-Mail-Adresse mit @students.zhaw.ch ein.", "warning");
        return;
    }
    state.emailValidationActive = false;
    const enteredEmail = dom.studentEmail ? dom.studentEmail.value.trim().toLowerCase() : "";

    setButtonsDisabled(true);
    try {
        const payload = await apiRequest("api/claim.php", {
            method: "POST",
            body: JSON.stringify({
                email: enteredEmail,
                pool,
                tabletConfirmed,
            }),
        });

        const storedEmail = getStoredStudentEmail();
        const assignmentEmail = payload.reused === true ? (storedEmail || enteredEmail) : enteredEmail;

        if (payload.reused !== true) {
            setStoredStudentEmail(enteredEmail);
        }

        renderAssignment(payload.assignment, payload.reused === true, assignmentEmail);
    } catch (error) {
        showMessage(error.message || "Die Zuweisung konnte nicht durchgeführt werden.", "danger");
    } finally {
        setButtonsDisabled(false);
    }
}

function renderAssignment(assignment, reused, assignmentEmail = "") {
    state.assignment = assignment;
    if (dom.entryCard) {
        dom.entryCard.classList.add("d-none");
    }
    clearMessage();
    clearAssignmentMessage();

    if (dom.assignmentVariant) {
        dom.assignmentVariant.textContent =
            assignment.pool === "tablet"
                ? "w.3DM Experiment 1, Variate 2D"
                : "w.3DM Experiment 1, Variante Text";
    }

    if (dom.assignmentStatus) {
        dom.assignmentStatus.textContent = assignmentEmail
            ? `Zugewiesen an ${assignmentEmail}`
            : "Zugewiesen an -";
    }

    if (dom.pinBadge) {
        dom.pinBadge.textContent = assignment.pin;
    }

    if (dom.surveyLink) {
        dom.surveyLink.href = assignment.surveyUrl;
    }

    if (assignment.agentUrl) {
        if (dom.agentLink) {
            dom.agentLink.href = assignment.agentUrl;
            dom.agentLink.classList.remove("d-none");
        }
        if (dom.agentActionGroup) {
            dom.agentActionGroup.classList.remove("d-none");
        }
        if (dom.assignmentHint) {
            dom.assignmentHint.textContent =
                "Öffnen Sie erst die Umfrage und danach den Chatbot in einem separaten Tab. Beide Links bleiben für diesen Browser erhalten.";
        }
    } else {
        if (dom.agentActionGroup) {
            dom.agentActionGroup.classList.add("d-none");
        }
        if (dom.agentLink) {
            dom.agentLink.classList.add("d-none");
            dom.agentLink.removeAttribute("href");
        }
        if (dom.assignmentHint) {
            dom.assignmentHint.textContent =
                "Öffnen Sie die Umfrage auf diesem Gerät. Ihre Angaben bleiben für diesen Browser erhalten.";
        }
    }

    if (reused) {
        showAssignmentMessage("Ihre bereits reservierten Informationen wurden erneut geladen.");
    }

    if (dom.assignmentState) {
        dom.assignmentState.classList.remove("d-none");
    }
}

function validateEmailField() {
    const email = dom.studentEmail ? dom.studentEmail.value.trim().toLowerCase() : "";
    const isValid = /^[^@\s]+@students\.zhaw\.ch$/i.test(email);
    if (dom.studentEmail) {
        if (state.emailValidationActive) {
            dom.studentEmail.classList.toggle("is-invalid", !isValid);
        } else {
            dom.studentEmail.classList.remove("is-invalid");
        }
    }
    return isValid;
}

function onStudentEmailInput() {
    if (!state.emailValidationActive) {
        return;
    }
    const isValid = validateEmailField();
    if (isValid && dom.messageArea && dom.messageArea.dataset.kind === "validation") {
        state.emailValidationActive = false;
        clearMessage();
    }
}

function setButtonsDisabled(disabled) {
    if (dom.claimForm) {
        for (const button of dom.claimForm.querySelectorAll("button")) {
            button.disabled = disabled;
        }
    }
    if (dom.tabletConfirmButton) {
        dom.tabletConfirmButton.disabled = disabled;
    }
}

function showMessage(message, type, kind = "general") {
    if (!dom.messageArea) {
        return;
    }
    dom.messageArea.className = `alert alert-${type} mt-4`;
    dom.messageArea.textContent = message;
    dom.messageArea.dataset.kind = kind;
    dom.messageArea.classList.remove("d-none");
}

function clearMessage() {
    if (!dom.messageArea) {
        return;
    }
    dom.messageArea.textContent = "";
    delete dom.messageArea.dataset.kind;
    dom.messageArea.className = "mt-4 d-none";
}

function showValidationMessage(message, type) {
    showMessage(message, type, "validation");
}

function showAssignmentMessage(message) {
    if (!dom.assignmentMessageArea) {
        return;
    }
    dom.assignmentMessageArea.textContent = message;
    dom.assignmentMessageArea.classList.remove("d-none");
}

function clearAssignmentMessage() {
    if (!dom.assignmentMessageArea) {
        return;
    }
    dom.assignmentMessageArea.textContent = "";
    dom.assignmentMessageArea.classList.add("d-none");
}

function setStoredStudentEmail(email) {
    try {
        localStorage.setItem(STORED_STUDENT_EMAIL_KEY, email);
    } catch (error) {
        // Ignore storage errors; UI will still work for current page session.
    }
}

function getStoredStudentEmail() {
    try {
        const value = localStorage.getItem(STORED_STUDENT_EMAIL_KEY);
        return typeof value === "string" ? value : "";
    } catch (error) {
        return "";
    }
}

async function copyLink(url) {
    if (!url) {
        return;
    }
    try {
        await navigator.clipboard.writeText(url);
        showAssignmentMessage("Link wurde in die Zwischenablage kopiert.");
    } catch (error) {
        showAssignmentMessage("Der Link konnte nicht automatisch kopiert werden.");
    }
}

async function apiRequest(url, options) {
    const response = await fetch(url, {
        headers: {
            "Content-Type": "application/json",
        },
        credentials: "same-origin",
        ...options,
    });

    let payload = {};
    try {
        payload = await response.json();
    } catch (error) {
        if (!response.ok) {
            throw new Error("Die Serverantwort konnte nicht gelesen werden.");
        }
    }

    if (!response.ok) {
        throw new Error(payload.message || "Die Anfrage ist fehlgeschlagen.");
    }

    return payload;
}
