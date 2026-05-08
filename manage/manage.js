const dom = {
    resetEmail: document.getElementById("resetEmail"),
    searchSuggestions: document.getElementById("searchSuggestions"),
    releaseAssignment: document.getElementById("releaseAssignment"),
    resetButton: document.getElementById("resetButton"),
    resetMessage: document.getElementById("resetMessage"),
    allowEmail: document.getElementById("allowEmail"),
    allowButton: document.getElementById("allowButton"),
    allowMessage: document.getElementById("allowMessage"),
};

let searchTimerId = null;

document.addEventListener("DOMContentLoaded", () => {
    if (dom.resetEmail) {
        dom.resetEmail.addEventListener("input", onResetEmailInput);
    }

    if (dom.resetButton) {
        dom.resetButton.addEventListener("click", onResetClick);
    }

    if (dom.allowButton) {
        dom.allowButton.addEventListener("click", onAllowClick);
    }
});

function onResetEmailInput() {
    clearMessage(dom.resetMessage);
    const query = dom.resetEmail.value.trim().toLowerCase();

    if (searchTimerId !== null) {
        clearTimeout(searchTimerId);
    }

    if (query.length < 2) {
        renderSuggestions([]);
        return;
    }

    searchTimerId = setTimeout(async () => {
        try {
            const payload = await apiRequest(`../api/manage/search_students.php?q=${encodeURIComponent(query)}`, {
                method: "GET",
            });
            renderSuggestions(payload.students || []);
        } catch (error) {
            renderSuggestions([]);
            showMessage(dom.resetMessage, error.message || "Suche konnte nicht geladen werden.", "danger");
        }
    }, 180);
}

function renderSuggestions(students) {
    dom.searchSuggestions.innerHTML = "";

    if (!Array.isArray(students) || students.length === 0) {
        dom.searchSuggestions.classList.add("d-none");
        return;
    }

    for (const student of students) {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "list-group-item list-group-item-action";

        const assignmentInfo = student.assignment
            ? ` (${student.assignment.pool}, PIN ${student.assignment.pin})`
            : "";
        button.textContent = student.email + assignmentInfo;
        button.addEventListener("click", () => {
            dom.resetEmail.value = student.email;
            dom.searchSuggestions.classList.add("d-none");
        });

        dom.searchSuggestions.appendChild(button);
    }

    dom.searchSuggestions.classList.remove("d-none");
}

async function onResetClick() {
    clearMessage(dom.resetMessage);
    const email = dom.resetEmail.value.trim().toLowerCase();
    const releaseAssignment = dom.releaseAssignment.checked === true;

    if (!isStudentEmail(email)) {
        showMessage(dom.resetMessage, "Bitte geben Sie eine gültige Studierenden-E-Mail-Adresse ein.", "warning");
        return;
    }

    setButtonLoading(dom.resetButton, true);
    try {
        const payload = await apiRequest("../api/manage/reset_student.php", {
            method: "POST",
            body: JSON.stringify({
                email,
                releaseAssignment,
            }),
        });

        const suffix = payload.releasedAssignment
            ? " und Zugang wurde freigegeben."
            : ".";
        showMessage(dom.resetMessage, `Reset für ${email} abgeschlossen${suffix}`, "success");
        renderSuggestions([]);
    } catch (error) {
        showMessage(dom.resetMessage, error.message || "Reset fehlgeschlagen.", "danger");
    } finally {
        setButtonLoading(dom.resetButton, false);
    }
}

async function onAllowClick() {
    clearMessage(dom.allowMessage);
    const email = dom.allowEmail.value.trim().toLowerCase();

    if (!isStudentEmail(email)) {
        showMessage(dom.allowMessage, "Bitte geben Sie eine gültige Studierenden-E-Mail-Adresse ein.", "warning");
        return;
    }

    setButtonLoading(dom.allowButton, true);
    try {
        const payload = await apiRequest("../api/manage/add_allowed_student.php", {
            method: "POST",
            body: JSON.stringify({
                email,
            }),
        });

        if (payload.created === true) {
            showMessage(dom.allowMessage, `E-Mail ${email} wurde hinzugefügt.`, "success");
        } else {
            showMessage(dom.allowMessage, `E-Mail ${email} ist bereits zugelassen.`, "info");
        }
    } catch (error) {
        showMessage(dom.allowMessage, error.message || "E-Mail konnte nicht hinzugefügt werden.", "danger");
    } finally {
        setButtonLoading(dom.allowButton, false);
    }
}

function setButtonLoading(button, loading) {
    if (!button) {
        return;
    }
    button.disabled = loading;
}

function showMessage(target, message, type) {
    target.className = `alert alert-${type} mb-0`;
    target.textContent = message;
    target.classList.remove("d-none");
}

function clearMessage(target) {
    target.className = "d-none";
    target.textContent = "";
}

function isStudentEmail(email) {
    return /^[^@\s]+@students\.zhaw\.ch$/i.test(email);
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
