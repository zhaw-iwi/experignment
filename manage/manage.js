const state = {
    dashboard: null,
    selectedExperimentId: null,
    view: "overview",
};

const dom = {
    brandHomeButton: document.getElementById("brandHomeButton"),
    allowedCount: document.getElementById("allowedCount"),
    reloadButton: document.getElementById("reloadButton"),
    phaseStepper: document.getElementById("phaseStepper"),
    pageTitle: document.getElementById("pageTitle"),
    messageArea: document.getElementById("messageArea"),
    overviewView: document.getElementById("overviewView"),
    allowedStudentsView: document.getElementById("allowedStudentsView"),
    experimentView: document.getElementById("experimentView"),
    gradingView: document.getElementById("gradingView"),
    overviewExperimentCard: document.getElementById("overviewExperimentCard"),
    experimentList: document.getElementById("experimentList"),
    newExperimentButton: document.getElementById("newExperimentButton"),
    allowedStudentsCard: document.getElementById("allowedStudentsCard"),
    allowedStudentList: document.getElementById("allowedStudentList"),
    experimentFormTitle: document.getElementById("experimentFormTitle"),
    experimentForm: document.getElementById("experimentForm"),
    backToOverviewButton: document.getElementById("backToOverviewButton"),
    cancelExperimentButton: document.getElementById("cancelExperimentButton"),
    deleteExperimentButton: document.getElementById("deleteExperimentButton"),
    experimentId: document.getElementById("experimentId"),
    experimentName: document.getElementById("experimentName"),
    experimentDescription: document.getElementById("experimentDescription"),
    eligibilityMode: document.getElementById("eligibilityMode"),
    conditionMode: document.getElementById("conditionMode"),
    sortOrder: document.getElementById("sortOrder"),
    isOpen: document.getElementById("isOpen"),
    requiresTimeSlot: document.getElementById("requiresTimeSlot"),
    conditionPanel: document.getElementById("conditionPanel"),
    conditionList: document.getElementById("conditionList"),
    conditionForm: document.getElementById("conditionForm"),
    conditionId: document.getElementById("conditionId"),
    conditionName: document.getElementById("conditionName"),
    conditionSortOrder: document.getElementById("conditionSortOrder"),
    allowForm: document.getElementById("allowForm"),
    allowEmail: document.getElementById("allowEmail"),
    bulkAllowForm: document.getElementById("bulkAllowForm"),
    bulkAllowEmails: document.getElementById("bulkAllowEmails"),
    accessPanel: document.getElementById("accessPanel"),
    fieldList: document.getElementById("fieldList"),
    fieldForm: document.getElementById("fieldForm"),
    fieldId: document.getElementById("fieldId"),
    fieldConditionId: document.getElementById("fieldConditionId"),
    fieldLabel: document.getElementById("fieldLabel"),
    fieldKey: document.getElementById("fieldKey"),
    valueType: document.getElementById("valueType"),
    valueSource: document.getElementById("valueSource"),
    fieldSortOrder: document.getElementById("fieldSortOrder"),
    sharedValue: document.getElementById("sharedValue"),
    fieldIsVisible: document.getElementById("fieldIsVisible"),
    poolImportForm: document.getElementById("poolImportForm"),
    poolConditionId: document.getElementById("poolConditionId"),
    poolTable: document.getElementById("poolTable"),
    poolCounts: document.getElementById("poolCounts"),
    poolRowList: document.getElementById("poolRowList"),
    eligibilityPanel: document.getElementById("eligibilityPanel"),
    assignForm: document.getElementById("assignForm"),
    assignEmail: document.getElementById("assignEmail"),
    assignConditionId: document.getElementById("assignConditionId"),
    randomizeForm: document.getElementById("randomizeForm"),
    randomSeed: document.getElementById("randomSeed"),
    allocationFields: document.getElementById("allocationFields"),
    eligibilityList: document.getElementById("eligibilityList"),
    slotPanel: document.getElementById("slotPanel"),
    slotList: document.getElementById("slotList"),
    slotForm: document.getElementById("slotForm"),
    slotId: document.getElementById("slotId"),
    slotLabel: document.getElementById("slotLabel"),
    slotCapacity: document.getElementById("slotCapacity"),
    slotStartsAt: document.getElementById("slotStartsAt"),
    slotEndsAt: document.getElementById("slotEndsAt"),
    slotSortOrder: document.getElementById("slotSortOrder"),
    slotIsActive: document.getElementById("slotIsActive"),
    slotChoiceList: document.getElementById("slotChoiceList"),
    gradingTitle: document.getElementById("gradingTitle"),
    backFromGradingButton: document.getElementById("backFromGradingButton"),
    participationRows: document.getElementById("participationRows"),
};

document.addEventListener("DOMContentLoaded", () => {
    wireEvents();
    dom.randomSeed.value = defaultSeed();
    loadDashboard();
});

function wireEvents() {
    dom.brandHomeButton.addEventListener("click", () => {
        openOverview();
    });
    dom.reloadButton.addEventListener("click", () => loadDashboard());
    dom.allowedCount.addEventListener("click", () => {
        openAllowedStudents();
    });
    dom.newExperimentButton.addEventListener("click", () => {
        openNewExperiment();
    });
    dom.backToOverviewButton.addEventListener("click", () => {
        openOverview();
    });
    dom.cancelExperimentButton.addEventListener("click", () => {
        openOverview();
    });
    dom.backFromGradingButton.addEventListener("click", () => {
        openOverview();
    });

    dom.experimentForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const payload = await postAction("save_experiment", {
            id: valueOrNull(dom.experimentId.value),
            name: dom.experimentName.value,
            description: dom.experimentDescription.value,
            eligibilityMode: dom.eligibilityMode.value,
            conditionMode: dom.conditionMode.value,
            sortOrder: dom.sortOrder.value,
            isOpen: dom.isOpen.checked,
            requiresTimeSlot: dom.requiresTimeSlot.checked,
        });
        state.selectedExperimentId = payload.experimentId;
        state.view = "experiment";
        await loadDashboard("Experiment gespeichert.");
    });

    dom.deleteExperimentButton.addEventListener("click", async () => {
        const experiment = selectedExperiment();
        if (!experiment) {
            showMessage("Bitte wählen Sie zuerst ein Experiment aus.", "warning");
            return;
        }
        await deleteExperiment(experiment);
    });

    dom.conditionForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const experiment = selectedExperiment();
        if (!experiment) {
            showMessage("Bitte speichern Sie zuerst das Experiment.", "warning");
            return;
        }
        await postAction("save_condition", {
            id: valueOrNull(dom.conditionId.value),
            experimentId: experiment.id,
            name: dom.conditionName.value,
            sortOrder: dom.conditionSortOrder.value,
        });
        clearConditionForm();
        await loadDashboard("Bedingung gespeichert.");
    });

    dom.allowForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const payload = await postAction("add_allowed_student", {
            email: dom.allowEmail.value,
        });
        dom.allowEmail.value = "";
        await loadDashboard(payload.created ? "E-Mail zugelassen." : "E-Mail war bereits zugelassen.");
    });

    dom.bulkAllowForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const payload = await postAction("bulk_add_allowed_students", {
            emails: dom.bulkAllowEmails.value,
        });
        dom.bulkAllowEmails.value = "";
        await loadDashboard(`${payload.created} E-Mails importiert, ${payload.skipped} bereits vorhanden.`);
    });

    dom.fieldForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const experiment = selectedExperiment();
        if (!experiment) {
            showMessage("Bitte speichern Sie zuerst das Experiment.", "warning");
            return;
        }
        await postAction("save_access_field", {
            id: valueOrNull(dom.fieldId.value),
            experimentId: experiment.id,
            conditionId: valueOrNull(dom.fieldConditionId.value),
            label: dom.fieldLabel.value,
            fieldKey: dom.fieldKey.value,
            valueType: dom.valueType.value,
            valueSource: dom.valueSource.value,
            sharedValue: dom.sharedValue.value,
            sortOrder: dom.fieldSortOrder.value,
            isVisible: dom.fieldIsVisible.checked,
        });
        clearFieldForm();
        await loadDashboard("Zugangsfeld gespeichert.");
    });

    dom.poolImportForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const experiment = selectedExperiment();
        if (!experiment) {
            showMessage("Bitte speichern Sie zuerst das Experiment.", "warning");
            return;
        }
        const payload = await postAction("import_pool_rows", {
            experimentId: experiment.id,
            conditionId: valueOrNull(dom.poolConditionId.value),
            table: dom.poolTable.value,
        });
        dom.poolTable.value = "";
        await loadDashboard(`${payload.imported} Pool-Zeilen importiert.`);
    });

    dom.assignForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const experiment = selectedExperiment();
        if (!experiment) {
            showMessage("Bitte speichern Sie zuerst das Experiment.", "warning");
            return;
        }
        await postAction("assign_student", {
            experimentId: experiment.id,
            email: dom.assignEmail.value,
            conditionId: valueOrNull(dom.assignConditionId.value),
        });
        dom.assignEmail.value = "";
        await loadDashboard("Studierende Person zugewiesen.");
    });

    dom.randomizeForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const experiment = selectedExperiment();
        if (!experiment) {
            showMessage("Bitte speichern Sie zuerst das Experiment.", "warning");
            return;
        }
        const allocations = Array.from(dom.allocationFields.querySelectorAll("input[data-condition-id]")).map((input) => ({
            conditionId: input.dataset.conditionId,
            percentage: input.value,
        }));
        const payload = await postAction("randomize", {
            experimentId: experiment.id,
            seed: dom.randomSeed.value,
            allocations,
        });
        await loadDashboard(`${payload.totalStudents} Studierende randomisiert.`);
    });

    dom.slotForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const experiment = selectedExperiment();
        if (!experiment) {
            showMessage("Bitte speichern Sie zuerst das Experiment.", "warning");
            return;
        }
        await postAction("save_slot", {
            id: valueOrNull(dom.slotId.value),
            experimentId: experiment.id,
            label: dom.slotLabel.value,
            capacity: dom.slotCapacity.value,
            startsAt: localDateTimeToSql(dom.slotStartsAt.value),
            endsAt: localDateTimeToSql(dom.slotEndsAt.value),
            sortOrder: dom.slotSortOrder.value,
            isActive: dom.slotIsActive.checked,
        });
        clearSlotForm();
        await loadDashboard("Zeitslot gespeichert.");
    });
}

async function loadDashboard(successMessage = "") {
    try {
        const dashboard = await apiRequest("../api/manage/dashboard.php", { method: "GET" });
        state.dashboard = dashboard;
        normalizeViewState();
        renderAll();
        if (typeof successMessage === "string" && successMessage !== "") {
            showMessage(successMessage, "success");
        } else {
            clearMessage();
        }
    } catch (error) {
        showMessage(error.message || "Dashboard konnte nicht geladen werden.", "danger");
    }
}

function normalizeViewState() {
    const experiment = selectedExperiment();
    if (state.view === "experiment" && state.selectedExperimentId === null) {
        return;
    }
    if ((state.view === "experiment" || state.view === "grading") && !experiment) {
        state.view = "overview";
        state.selectedExperimentId = null;
    }
}

function renderAll() {
    const dashboard = state.dashboard || {
        allowedStudentCount: 0,
        allowedStudents: [],
        experiments: [],
        participations: [],
    };

    renderAllowedCount(dashboard.allowedStudentCount || 0);
    renderViews();
    renderPageTitle();
    renderPhaseStepper();
    renderExperimentList();
    renderAllowedStudentList();
    renderExperimentForm();
    updateExperimentDependentPanels();
    renderConditionSection();
    renderFieldSection();
    renderPoolCounts();
    renderPoolRows();
    renderEligibilitySection();
    renderEligibilityList();
    renderSlotSection();
    renderSlotChoices();
    renderGrading();
}

function renderAllowedCount(count) {
    const icon = document.createElement("i");
    icon.className = "bi bi-pencil-square";
    icon.setAttribute("aria-hidden", "true");

    const text = document.createElement("span");
    text.textContent = `${count} Studierende`;

    dom.allowedCount.replaceChildren(icon, text);
    dom.allowedCount.setAttribute("aria-label", `${count} zugelassene Studierende bearbeiten`);
}

function renderViews() {
    dom.overviewView.classList.toggle("d-none", state.view !== "overview");
    dom.allowedStudentsView.classList.toggle("d-none", state.view !== "allowed");
    dom.experimentView.classList.toggle("d-none", state.view !== "experiment");
    dom.gradingView.classList.toggle("d-none", state.view !== "grading");
}

function renderPageTitle() {
    const experiment = selectedExperiment();
    if (state.view === "experiment") {
        dom.pageTitle.textContent = experiment ? experiment.name : "Neues Experiment";
        return;
    }
    if (state.view === "grading") {
        dom.pageTitle.textContent = experiment ? `${experiment.name} Anrechnung` : "Anrechnung";
        return;
    }
    if (state.view === "allowed") {
        dom.pageTitle.textContent = "Zugelassene Studierende";
        return;
    }
    dom.pageTitle.textContent = "Experimente";
}

function renderPhaseStepper() {
    dom.phaseStepper.innerHTML = "";
    dom.phaseStepper.classList.toggle("is-single", state.view === "allowed");

    if (state.view === "allowed") {
        addPhaseStep({
            number: 0,
            title: "Zugelassene Studierende",
            meta: "Globale Liste aller E-Mail-Adressen, die Experimente sehen können.",
            current: true,
            disabled: false,
            onClick: null,
        });
        return;
    }

    const experiment = selectedExperiment();
    const experimentTitle = experiment ? experiment.name : "Experiment bearbeiten";
    const gradingAction = experiment ? () => openGrading(experiment.id) : null;

    addPhaseStep({
        number: 1,
        title: "Experimente",
        meta: "Liste und neues Experiment",
        current: state.view === "overview",
        disabled: false,
        onClick: state.view === "overview" ? null : openOverview,
    });
    addPhaseStep({
        number: 2,
        title: state.view === "experiment" && !experiment ? "Neues Experiment" : experimentTitle,
        meta: state.view === "overview" ? "Experiment wählen oder neu erstellen" : experiment ? "Setup bearbeiten" : "Erst Grunddaten speichern",
        current: state.view === "experiment",
        disabled: state.view === "overview",
        onClick: experiment && state.view !== "experiment" ? () => openExperiment(experiment.id) : null,
    });
    addPhaseStep({
        number: 3,
        title: "Anrechnung",
        meta: experiment ? "Teilnahmen verwalten" : "Nach dem Speichern verfügbar",
        current: state.view === "grading",
        disabled: !experiment,
        onClick: state.view !== "grading" ? gradingAction : null,
    });
}

function addPhaseStep({ number, title, meta, current, disabled, onClick }) {
    const item = document.createElement("li");
    item.className = "mc-step";
    if (disabled) {
        item.classList.add("is-disabled");
        item.setAttribute("aria-disabled", "true");
    } else {
        item.classList.add("is-open");
    }
    if (current) {
        item.classList.add("is-current");
        item.setAttribute("aria-current", "step");
    }
    if (onClick && !disabled) {
        item.classList.add("is-clickable");
        item.tabIndex = 0;
        item.setAttribute("role", "button");
        item.addEventListener("click", onClick);
        item.addEventListener("keydown", (event) => {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                onClick();
            }
        });
    }

    const numberNode = document.createElement("span");
    numberNode.className = "mc-step-number";
    numberNode.textContent = String(number);

    const text = document.createElement("span");
    const titleNode = document.createElement("span");
    titleNode.className = "mc-step-title";
    titleNode.textContent = title;
    const metaNode = document.createElement("span");
    metaNode.className = "mc-step-meta";
    metaNode.textContent = meta;
    text.append(titleNode, metaNode);

    item.append(numberNode, text);
    dom.phaseStepper.appendChild(item);
}

function openAllowedStudents() {
    state.view = "allowed";
    state.selectedExperimentId = null;
    renderAll();
}

function openOverview() {
    state.view = "overview";
    state.selectedExperimentId = null;
    renderAll();
}

function openNewExperiment() {
    state.view = "experiment";
    state.selectedExperimentId = null;
    clearExperimentForm();
    clearExperimentDependentForms();
    renderAll();
    dom.experimentName.focus();
}

function openExperiment(experimentId) {
    state.view = "experiment";
    state.selectedExperimentId = experimentId;
    clearExperimentDependentForms();
    renderAll();
}

function openGrading(experimentId) {
    state.view = "grading";
    state.selectedExperimentId = experimentId;
    renderAll();
}

function renderExperimentList() {
    dom.experimentList.innerHTML = "";
    const experiments = state.dashboard?.experiments || [];
    if (experiments.length === 0) {
        dom.experimentList.appendChild(emptyListGroupItem("Keine Experimente vorhanden."));
        return;
    }

    for (const experiment of experiments) {
        const item = document.createElement("div");
        item.className = "list-group-item";

        const row = document.createElement("div");
        row.className = "d-flex justify-content-between gap-3 align-items-start";

        const text = document.createElement("div");
        text.className = "min-width-0";
        const title = document.createElement("div");
        title.className = "d-flex flex-wrap gap-2 align-items-center";
        const strong = document.createElement("strong");
        strong.textContent = experiment.name;
        const openBadge = document.createElement("span");
        openBadge.className = `badge ${experiment.isOpen ? "text-bg-success" : "text-bg-secondary"}`;
        openBadge.textContent = experiment.isOpen ? "offen" : "geschlossen";
        title.append(strong, openBadge);

        const meta = document.createElement("div");
        meta.className = "small text-secondary mt-1";
        meta.textContent = `${experiment.counts.participations} Zuweisungen, ${experiment.counts.confirmed} angerechnet`;
        text.append(title, meta);

        const dropdown = experimentActionDropdown(experiment);
        row.append(text, dropdown);
        item.appendChild(row);
        dom.experimentList.appendChild(item);
    }
}

function experimentActionDropdown(experiment) {
    const wrapper = document.createElement("div");
    wrapper.className = "dropdown flex-shrink-0";

    const toggle = document.createElement("button");
    toggle.type = "button";
    toggle.className = "btn btn-outline-secondary btn-sm icon-menu";
    toggle.setAttribute("data-bs-toggle", "dropdown");
    toggle.setAttribute("aria-expanded", "false");
    toggle.setAttribute("aria-label", `${experiment.name} Aktionen`);
    toggle.textContent = "⋮";

    const menu = document.createElement("ul");
    menu.className = "dropdown-menu dropdown-menu-end";
    menu.appendChild(dropdownItem("Bearbeiten", () => openExperiment(experiment.id)));
    menu.appendChild(dropdownItem("Anrechnung", () => openGrading(experiment.id)));
    menu.appendChild(dropdownDivider());
    menu.appendChild(dropdownItem("Löschen", () => deleteExperiment(experiment), "text-danger"));

    wrapper.append(toggle, menu);
    return wrapper;
}

function dropdownItem(label, onClick, extraClass = "") {
    const listItem = document.createElement("li");
    const button = document.createElement("button");
    button.type = "button";
    button.className = `dropdown-item ${extraClass}`;
    button.textContent = label;
    button.addEventListener("click", onClick);
    listItem.appendChild(button);
    return listItem;
}

function dropdownDivider() {
    const listItem = document.createElement("li");
    const divider = document.createElement("hr");
    divider.className = "dropdown-divider";
    listItem.appendChild(divider);
    return listItem;
}

async function deleteExperiment(experiment) {
    if (!window.confirm(`Experiment "${experiment.name}" mit allen zugehörigen Daten löschen?`)) {
        return;
    }
    await postAction("delete_experiment", { experimentId: experiment.id });
    state.selectedExperimentId = null;
    state.view = "overview";
    await loadDashboard("Experiment gelöscht.");
}

function renderAllowedStudentList() {
    dom.allowedStudentList.innerHTML = "";
    const students = state.dashboard?.allowedStudents || [];
    if (students.length === 0) {
        dom.allowedStudentList.appendChild(emptyListGroupItem("Keine Studierenden zugelassen."));
        return;
    }

    for (const student of students) {
        const item = document.createElement("div");
        item.className = "list-group-item";
        const row = document.createElement("div");
        row.className = "d-flex justify-content-between gap-3 align-items-start";

        const text = document.createElement("div");
        text.className = "min-width-0";
        const email = document.createElement("strong");
        email.textContent = student.email;
        const meta = document.createElement("div");
        meta.className = "small text-secondary mt-1";
        meta.textContent = `${student.participationCount} Zuweisungen, ${student.confirmedCount} angerechnet, ${student.eligibilityCount} Freigaben`;
        text.append(email, meta);

        const remove = smallButton("Entfernen", "outline-danger");
        remove.disabled = student.participationCount > 0;
        remove.title = student.participationCount > 0
            ? "Studierende mit Zuweisungen können nicht aus der globalen Liste entfernt werden."
            : "Aus globaler Zulassungsliste entfernen";
        remove.addEventListener("click", async () => {
            if (!window.confirm(`${student.email} aus der globalen Zulassungsliste entfernen?`)) {
                return;
            }
            await postAction("delete_allowed_student", { email: student.email });
            await loadDashboard("E-Mail aus der Zulassungsliste entfernt.");
        });

        row.append(text, remove);
        item.appendChild(row);
        dom.allowedStudentList.appendChild(item);
    }
}

function renderExperimentForm() {
    if (state.view !== "experiment") {
        return;
    }

    const experiment = selectedExperiment();
    if (!experiment) {
        if (dom.experimentId.value !== "") {
            clearExperimentForm();
        }
        dom.experimentFormTitle.textContent = "Neues Experiment";
        setExperimentActionState(true);
        return;
    }

    dom.experimentFormTitle.textContent = "Experiment bearbeiten";
    setExperimentActionState(false);
    dom.experimentId.value = experiment.id;
    dom.experimentName.value = experiment.name || "";
    dom.experimentDescription.value = experiment.description || "";
    dom.eligibilityMode.value = experiment.eligibilityMode;
    dom.conditionMode.value = experiment.conditionMode;
    dom.sortOrder.value = experiment.sortOrder;
    dom.isOpen.checked = experiment.isOpen;
    dom.requiresTimeSlot.checked = experiment.requiresTimeSlot;
}

function updateExperimentDependentPanels() {
    const hasSavedExperiment = selectedExperiment() !== null;
    for (const panel of [dom.conditionPanel, dom.accessPanel, dom.eligibilityPanel, dom.slotPanel]) {
        panel.classList.toggle("d-none", !hasSavedExperiment || state.view !== "experiment");
    }
}

function setExperimentActionState(isNewExperiment) {
    dom.cancelExperimentButton.classList.toggle("d-none", !isNewExperiment);
    dom.deleteExperimentButton.classList.toggle("d-none", isNewExperiment);
    dom.deleteExperimentButton.disabled = isNewExperiment;
}

function renderConditionSection() {
    const experiment = selectedExperiment();
    dom.conditionList.innerHTML = "";
    if (!experiment) {
        return;
    }

    for (const condition of experiment.conditions || []) {
        const item = compactItem(condition.name, `Reihenfolge ${condition.sortOrder}`);
        const edit = smallButton("Bearbeiten", "outline-primary");
        edit.addEventListener("click", () => {
            dom.conditionId.value = condition.id;
            dom.conditionName.value = condition.name;
            dom.conditionSortOrder.value = condition.sortOrder;
        });
        const remove = smallButton("Löschen", "outline-danger");
        remove.addEventListener("click", async () => {
            if (!window.confirm(`Bedingung "${condition.name}" löschen?`)) {
                return;
            }
            await postAction("delete_condition", { conditionId: condition.id });
            clearConditionForm();
            await loadDashboard("Bedingung gelöscht.");
        });
        item.appendChild(actionRow([edit, remove]));
        dom.conditionList.appendChild(item);
    }

    if ((experiment.conditions || []).length === 0) {
        dom.conditionList.appendChild(emptyText("Keine Bedingungen definiert."));
    }
}

function renderFieldSection() {
    const experiment = selectedExperiment();
    dom.fieldList.innerHTML = "";
    fillConditionSelect(dom.fieldConditionId, experiment, "Experimentweit");
    fillConditionSelect(dom.poolConditionId, experiment, "Experimentweit");

    if (!experiment) {
        return;
    }

    for (const field of experiment.accessFields || []) {
        const conditionName = conditionNameById(experiment, field.conditionId) || "experimentweit";
        const item = compactItem(field.label, `${conditionName}, ${field.valueSource}, ${field.valueType}`);
        const edit = smallButton("Bearbeiten", "outline-primary");
        edit.addEventListener("click", () => {
            dom.fieldId.value = field.id;
            dom.fieldConditionId.value = field.conditionId || "";
            dom.fieldLabel.value = field.label;
            dom.fieldKey.value = field.key;
            dom.valueType.value = field.valueType;
            dom.valueSource.value = field.valueSource;
            dom.sharedValue.value = field.sharedValue || "";
            dom.fieldSortOrder.value = field.sortOrder;
            dom.fieldIsVisible.checked = field.isVisible;
        });
        const remove = smallButton("Löschen", "outline-danger");
        remove.addEventListener("click", async () => {
            if (!window.confirm(`Zugangsfeld "${field.label}" löschen?`)) {
                return;
            }
            await postAction("delete_access_field", { fieldId: field.id });
            clearFieldForm();
            await loadDashboard("Zugangsfeld gelöscht.");
        });
        item.appendChild(actionRow([edit, remove]));
        dom.fieldList.appendChild(item);
    }

    if ((experiment.accessFields || []).length === 0) {
        dom.fieldList.appendChild(emptyText("Keine Zugangsfelder definiert."));
    }
}

function renderPoolCounts() {
    const experiment = selectedExperiment();
    if (!experiment) {
        dom.poolCounts.textContent = "";
        return;
    }

    const parts = (experiment.poolCounts || []).map((count) => {
        const conditionName = conditionNameById(experiment, count.conditionId) || "experimentweit";
        return `${conditionName}: ${count.available}/${count.total} frei`;
    });
    dom.poolCounts.textContent = parts.length > 0 ? parts.join(" · ") : "Kein Zugangsdaten-Pool vorhanden.";
}

function renderPoolRows() {
    const experiment = selectedExperiment();
    dom.poolRowList.innerHTML = "";
    if (!experiment) {
        return;
    }

    const rows = experiment.accessPoolRows || [];
    for (const poolRow of rows) {
        const conditionName = poolRow.conditionName || "experimentweit";
        const values = (poolRow.values || []).map((value) => `${value.label}: ${value.value}`).join(" · ");
        const assigned = poolRow.isAssigned
            ? `zugewiesen an ${poolRow.assignedStudentEmail || "unbekannt"}`
            : "frei";
        const item = compactItem(`#${poolRow.id} · ${conditionName}`, `${assigned}${values ? " · " + values : ""}`);
        const remove = smallButton("Löschen", "outline-danger");
        remove.disabled = poolRow.isAssigned;
        remove.title = poolRow.isAssigned ? "Zugewiesene Pool-Zeilen zuerst zurücksetzen." : "Pool-Zeile löschen";
        remove.addEventListener("click", async () => {
            if (!window.confirm(`Pool-Zeile #${poolRow.id} löschen?`)) {
                return;
            }
            await postAction("delete_pool_row", { poolRowId: poolRow.id });
            await loadDashboard("Pool-Zeile gelöscht.");
        });
        item.appendChild(actionRow([remove]));
        dom.poolRowList.appendChild(item);
    }

    if (rows.length === 0) {
        dom.poolRowList.appendChild(emptyText("Keine Pool-Zeilen vorhanden."));
    }
}

function renderEligibilitySection() {
    const experiment = selectedExperiment();
    fillConditionSelect(dom.assignConditionId, experiment, "Keine");
    dom.allocationFields.innerHTML = "";

    if (!experiment || (experiment.conditions || []).length === 0) {
        dom.allocationFields.appendChild(emptyText("Randomisierung benötigt Bedingungen."));
        return;
    }

    const defaultPercentage = Math.round(100 / experiment.conditions.length);
    for (const condition of experiment.conditions) {
        const wrapper = document.createElement("label");
        wrapper.className = "input-group input-group-sm";
        wrapper.innerHTML = `
            <span class="input-group-text">${escapeHtml(condition.name)}</span>
            <input class="form-control" type="number" min="0.01" step="0.01" value="${defaultPercentage}" data-condition-id="${condition.id}">
            <span class="input-group-text">%</span>
        `;
        dom.allocationFields.appendChild(wrapper);
    }
}

function renderEligibilityList() {
    const experiment = selectedExperiment();
    dom.eligibilityList.innerHTML = "";
    if (!experiment) {
        return;
    }

    const rows = experiment.eligibilities || [];
    for (const eligibility of rows) {
        const condition = eligibility.conditionName || "keine Bedingung";
        const claimed = eligibility.hasParticipation ? "bereits zugewiesen" : "noch nicht zugewiesen";
        const item = compactItem(eligibility.email, `${condition} · ${eligibility.source} · ${claimed}`);
        const remove = smallButton("Entfernen", "outline-danger");
        remove.addEventListener("click", async () => {
            if (!window.confirm(`Freigabe für ${eligibility.email} entfernen?`)) {
                return;
            }
            await postAction("delete_eligibility", { eligibilityId: eligibility.id });
            await loadDashboard("Freigabe entfernt.");
        });
        item.appendChild(actionRow([remove]));
        dom.eligibilityList.appendChild(item);
    }

    if (rows.length === 0) {
        dom.eligibilityList.appendChild(emptyText("Keine individuellen Freigaben vorhanden."));
    }

    for (const run of experiment.randomizationRuns || []) {
        const allocations = (run.allocations || [])
            .map((allocation) => `${allocation.conditionName}: ${allocation.assignedCount}`)
            .join(" · ");
        dom.eligibilityList.appendChild(compactItem(`Randomisierung #${run.id}`, `${run.totalStudents} Studierende · Seed ${run.seed}${allocations ? " · " + allocations : ""}`));
    }
}

function renderSlotSection() {
    const experiment = selectedExperiment();
    dom.slotList.innerHTML = "";
    if (!experiment) {
        return;
    }

    for (const slot of experiment.timeSlots || []) {
        const item = compactItem(slotLabel(slot), `${slot.remainingCapacity}/${slot.capacity} frei`);
        const edit = smallButton("Bearbeiten", "outline-primary");
        edit.addEventListener("click", () => {
            dom.slotId.value = slot.id;
            dom.slotLabel.value = slot.label;
            dom.slotCapacity.value = slot.capacity;
            dom.slotStartsAt.value = sqlDateTimeToLocal(slot.startsAt);
            dom.slotEndsAt.value = sqlDateTimeToLocal(slot.endsAt);
            dom.slotSortOrder.value = slot.sortOrder || 0;
            dom.slotIsActive.checked = slot.isActive;
        });
        const remove = smallButton("Löschen", "outline-danger");
        remove.disabled = slot.chosenCount > 0;
        remove.title = slot.chosenCount > 0 ? "Zeitslots mit Auswahl können nicht gelöscht werden." : "Zeitslot löschen";
        remove.addEventListener("click", async () => {
            if (!window.confirm(`Zeitslot "${slot.label}" löschen?`)) {
                return;
            }
            await postAction("delete_slot", { slotId: slot.id });
            clearSlotForm();
            await loadDashboard("Zeitslot gelöscht.");
        });
        item.appendChild(actionRow([edit, remove]));
        dom.slotList.appendChild(item);
    }

    if ((experiment.timeSlots || []).length === 0) {
        dom.slotList.appendChild(emptyText("Keine Zeitslots definiert."));
    }
}

function renderSlotChoices() {
    const experiment = selectedExperiment();
    dom.slotChoiceList.innerHTML = "";
    if (!experiment) {
        return;
    }

    const choicesBySlot = new Map();
    for (const choice of experiment.slotChoices || []) {
        if (!choicesBySlot.has(choice.slotId)) {
            choicesBySlot.set(choice.slotId, []);
        }
        choicesBySlot.get(choice.slotId).push(choice);
    }

    for (const slot of experiment.timeSlots || []) {
        const choices = choicesBySlot.get(slot.id) || [];
        if (choices.length === 0) {
            continue;
        }
        const emails = choices.map((choice) => {
            const appointment = choice.appointmentText ? ` (${choice.appointmentText})` : "";
            return `${choice.email}${appointment}`;
        }).join(", ");
        dom.slotChoiceList.appendChild(compactItem(`Auswahl: ${slot.label}`, emails));
    }

    if ((experiment.slotChoices || []).length === 0) {
        dom.slotChoiceList.appendChild(emptyText("Keine Zeitslot-Auswahlen vorhanden."));
    }
}

function renderGrading() {
    const experiment = selectedExperiment();
    dom.participationRows.innerHTML = "";
    dom.gradingTitle.textContent = experiment ? `${experiment.name} Anrechnung` : "Anrechnung";

    if (state.view !== "grading" || !experiment) {
        return;
    }

    const rows = (state.dashboard.participations || []).filter((participation) => {
        return participation.experimentId === experiment.id;
    });

    for (const participation of rows) {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${escapeHtml(participation.email)}</td>
            <td>${escapeHtml(participation.conditionName || "-")}</td>
            <td>${escapeHtml(participation.slotLabel || "-")}</td>
            <td></td>
            <td>${participation.confirmed ? "Ja" : "Nein"}</td>
            <td class="text-end"></td>
        `;

        const appointmentCell = row.children[3];
        const appointmentGroup = document.createElement("div");
        appointmentGroup.className = "input-group input-group-sm";
        const input = document.createElement("input");
        input.className = "form-control";
        input.value = participation.appointmentText || "";
        input.placeholder = "z.B. 09:30";
        const save = document.createElement("button");
        save.className = "btn btn-outline-primary";
        save.type = "button";
        save.textContent = "OK";
        save.addEventListener("click", async () => {
            await postAction("save_appointment", {
                participationId: participation.id,
                appointmentText: input.value,
            });
            await loadDashboard("Uhrzeit gespeichert.");
        });
        appointmentGroup.append(input, save);
        appointmentCell.appendChild(appointmentGroup);

        const actions = row.children[5];
        const confirm = smallButton(participation.confirmed ? "Entfernen" : "Anrechnen", participation.confirmed ? "outline-secondary" : "success");
        confirm.addEventListener("click", async () => {
            await postAction("toggle_confirmation", { participationId: participation.id });
            await loadDashboard("Anrechnung aktualisiert.");
        });
        const reset = smallButton("Reset", "outline-danger");
        reset.addEventListener("click", async () => {
            if (!window.confirm("Diese Zuweisung zurücksetzen und Zugangsdaten freigeben?")) {
                return;
            }
            await postAction("reset_participation", {
                participationId: participation.id,
                releaseAccess: true,
            });
            await loadDashboard("Zuweisung zurückgesetzt.");
        });
        actions.append(confirm, document.createTextNode(" "), reset);

        dom.participationRows.appendChild(row);
    }

    if (rows.length === 0) {
        const row = document.createElement("tr");
        const cell = document.createElement("td");
        cell.colSpan = 6;
        cell.className = "text-secondary py-3";
        cell.textContent = "Keine Zuweisungen für dieses Experiment vorhanden.";
        row.appendChild(cell);
        dom.participationRows.appendChild(row);
    }
}

function selectedExperiment() {
    return (state.dashboard?.experiments || []).find((experiment) => experiment.id === state.selectedExperimentId) || null;
}

function clearExperimentForm() {
    dom.experimentFormTitle.textContent = "Neues Experiment";
    dom.experimentId.value = "";
    dom.experimentName.value = "";
    dom.experimentDescription.value = "";
    dom.eligibilityMode.value = "selected";
    dom.conditionMode.value = "none";
    dom.sortOrder.value = "0";
    dom.isOpen.checked = false;
    dom.requiresTimeSlot.checked = false;
    setExperimentActionState(true);
}

function clearExperimentDependentForms() {
    clearConditionForm();
    clearFieldForm();
    clearSlotForm();
    dom.assignEmail.value = "";
    dom.poolTable.value = "";
}

function clearConditionForm() {
    dom.conditionId.value = "";
    dom.conditionName.value = "";
    dom.conditionSortOrder.value = "0";
}

function clearFieldForm() {
    dom.fieldId.value = "";
    dom.fieldConditionId.value = "";
    dom.fieldLabel.value = "";
    dom.fieldKey.value = "";
    dom.valueType.value = "text";
    dom.valueSource.value = "shared";
    dom.sharedValue.value = "";
    dom.fieldSortOrder.value = "0";
    dom.fieldIsVisible.checked = true;
}

function clearSlotForm() {
    dom.slotId.value = "";
    dom.slotLabel.value = "";
    dom.slotCapacity.value = "1";
    dom.slotStartsAt.value = "";
    dom.slotEndsAt.value = "";
    dom.slotSortOrder.value = "0";
    dom.slotIsActive.checked = true;
}

function fillConditionSelect(select, experiment, emptyLabel) {
    const current = select.value;
    select.innerHTML = "";
    const empty = document.createElement("option");
    empty.value = "";
    empty.textContent = emptyLabel;
    select.appendChild(empty);

    for (const condition of experiment?.conditions || []) {
        const option = document.createElement("option");
        option.value = condition.id;
        option.textContent = condition.name;
        select.appendChild(option);
    }
    select.value = current;
}

function conditionNameById(experiment, conditionId) {
    const condition = (experiment.conditions || []).find((item) => item.id === conditionId);
    return condition ? condition.name : "";
}

function compactItem(title, meta) {
    const item = document.createElement("div");
    item.className = "compact-item";
    const text = document.createElement("div");
    text.innerHTML = `<strong>${escapeHtml(title)}</strong><div class="small text-secondary">${escapeHtml(meta)}</div>`;
    item.appendChild(text);
    return item;
}

function emptyText(text) {
    const item = document.createElement("div");
    item.className = "text-secondary small";
    item.textContent = text;
    return item;
}

function emptyListGroupItem(text) {
    const item = document.createElement("div");
    item.className = "list-group-item text-secondary";
    item.textContent = text;
    return item;
}

function smallButton(label, variant) {
    const button = document.createElement("button");
    button.type = "button";
    button.className = `btn btn-${variant} btn-sm`;
    button.textContent = label;
    return button;
}

function actionRow(buttons) {
    const row = document.createElement("div");
    row.className = "action-row";
    for (const button of buttons) {
        row.appendChild(button);
    }
    return row;
}

async function postAction(action, payload) {
    clearMessage();
    try {
        return await apiRequest("../api/manage/actions.php", {
            method: "POST",
            body: JSON.stringify({
                action,
                ...payload,
            }),
        });
    } catch (error) {
        showMessage(error.message || "Die Aktion konnte nicht abgeschlossen werden.", "danger");
        throw error;
    }
}

function valueOrNull(value) {
    if (value === null || value === undefined || String(value).trim() === "") {
        return null;
    }
    const numeric = Number(value);
    return Number.isFinite(numeric) ? numeric : value;
}

function slotLabel(slot) {
    const time = [formatDateTime(slot.startsAt), formatDateTime(slot.endsAt)].filter((value) => value !== "-").join(" - ");
    return time ? `${slot.label} · ${time}` : slot.label;
}

function formatDateTime(value) {
    if (!value) {
        return "-";
    }
    const date = new Date(String(value).replace(" ", "T"));
    if (Number.isNaN(date.getTime())) {
        return value;
    }
    return new Intl.DateTimeFormat("de-CH", {
        dateStyle: "short",
        timeStyle: "short",
    }).format(date);
}

function sqlDateTimeToLocal(value) {
    if (!value) {
        return "";
    }
    return String(value).replace(" ", "T").slice(0, 16);
}

function localDateTimeToSql(value) {
    return value ? value.replace("T", " ") + ":00" : "";
}

function defaultSeed() {
    return new Date().toISOString().replace(/[^\d]/g, "").slice(0, 14);
}

function showMessage(message, type) {
    dom.messageArea.className = `alert alert-${type}`;
    dom.messageArea.textContent = message;
    dom.messageArea.classList.remove("d-none");
}

function clearMessage() {
    dom.messageArea.className = "d-none";
    dom.messageArea.textContent = "";
}

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
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
