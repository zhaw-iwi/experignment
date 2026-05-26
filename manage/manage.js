const state = {
    dashboard: null,
    selectedExperimentId: null,
    view: "overview",
    participantDraftMode: "selected",
    participantDraftEmails: new Set(),
    conditionAssignmentDraft: new Map(),
    conditionAssignmentSource: "manual",
    gradingFilters: {},
    gradingSort: { key: "", direction: "asc" },
    bulkFilters: {},
    bulkSort: { key: "", direction: "asc" },
    bulkSelectedIds: new Set(),
};

const PAGE_ALERT_TIMEOUT_MS = 5000;
const messageTimers = new WeakMap();
let activeRequestCount = 0;

const dom = {
    brandHomeButton: document.getElementById("brandHomeButton"),
    allowedCount: document.getElementById("allowedCount"),
    reloadButton: document.getElementById("reloadButton"),
    systemStatus: document.getElementById("systemStatus"),
    systemStatusText: document.getElementById("systemStatusText"),
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
    conditionFormTitle: document.getElementById("conditionFormTitle"),
    conditionForm: document.getElementById("conditionForm"),
    cancelConditionButton: document.getElementById("cancelConditionButton"),
    conditionId: document.getElementById("conditionId"),
    conditionName: document.getElementById("conditionName"),
    conditionSortOrder: document.getElementById("conditionSortOrder"),
    allowForm: document.getElementById("allowForm"),
    allowEmail: document.getElementById("allowEmail"),
    bulkAllowForm: document.getElementById("bulkAllowForm"),
    bulkAllowEmails: document.getElementById("bulkAllowEmails"),
    accessPanel: document.getElementById("accessPanel"),
    fieldList: document.getElementById("fieldList"),
    fieldFormTitle: document.getElementById("fieldFormTitle"),
    fieldForm: document.getElementById("fieldForm"),
    cancelFieldButton: document.getElementById("cancelFieldButton"),
    fieldId: document.getElementById("fieldId"),
    fieldConditionId: document.getElementById("fieldConditionId"),
    fieldLabel: document.getElementById("fieldLabel"),
    fieldKey: document.getElementById("fieldKey"),
    valueType: document.getElementById("valueType"),
    valueSource: document.getElementById("valueSource"),
    fieldSortOrder: document.getElementById("fieldSortOrder"),
    sharedValueGroup: document.getElementById("sharedValueGroup"),
    sharedValue: document.getElementById("sharedValue"),
    fieldIsVisible: document.getElementById("fieldIsVisible"),
    poolPanel: document.getElementById("poolPanel"),
    poolSummary: document.getElementById("poolSummary"),
    managePoolButton: document.getElementById("managePoolButton"),
    clearPoolButton: document.getElementById("clearPoolButton"),
    poolRowList: document.getElementById("poolRowList"),
    poolModal: document.getElementById("poolModal"),
    poolModalMessage: document.getElementById("poolModalMessage"),
    poolModalConditionGroup: document.getElementById("poolModalConditionGroup"),
    poolModalConditionId: document.getElementById("poolModalConditionId"),
    poolConditionHint: document.getElementById("poolConditionHint"),
    poolSample: document.getElementById("poolSample"),
    poolTable: document.getElementById("poolTable"),
    poolSaveButton: document.getElementById("poolSaveButton"),
    staffValueSection: document.getElementById("staffValueSection"),
    staffValueSummary: document.getElementById("staffValueSummary"),
    manageStaffValuesButton: document.getElementById("manageStaffValuesButton"),
    staffValueModal: document.getElementById("staffValueModal"),
    staffValueModalMessage: document.getElementById("staffValueModalMessage"),
    staffValueHeaderRow: document.getElementById("staffValueHeaderRow"),
    staffValueRows: document.getElementById("staffValueRows"),
    staffValueSaveButton: document.getElementById("staffValueSaveButton"),
    participantPanel: document.getElementById("participantPanel"),
    participantSummary: document.getElementById("participantSummary"),
    participantList: document.getElementById("participantList"),
    manageParticipantsButton: document.getElementById("manageParticipantsButton"),
    clearParticipantsButton: document.getElementById("clearParticipantsButton"),
    conditionAssignmentPanel: document.getElementById("conditionAssignmentPanel"),
    conditionAssignmentSummary: document.getElementById("conditionAssignmentSummary"),
    conditionAssignmentList: document.getElementById("conditionAssignmentList"),
    manageConditionAssignmentsButton: document.getElementById("manageConditionAssignmentsButton"),
    clearConditionAssignmentsButton: document.getElementById("clearConditionAssignmentsButton"),
    participantModal: document.getElementById("participantModal"),
    participantModalMessage: document.getElementById("participantModalMessage"),
    participantModalSelectedList: document.getElementById("participantModalSelectedList"),
    participantSelectedCount: document.getElementById("participantSelectedCount"),
    participantSelectAllButton: document.getElementById("participantSelectAllButton"),
    participantRandomCount: document.getElementById("participantRandomCount"),
    participantRandomSeed: document.getElementById("participantRandomSeed"),
    participantRandomButton: document.getElementById("participantRandomButton"),
    participantSearchInput: document.getElementById("participantSearchInput"),
    participantSuggestionList: document.getElementById("participantSuggestionList"),
    participantSaveButton: document.getElementById("participantSaveButton"),
    conditionAssignmentModal: document.getElementById("conditionAssignmentModal"),
    conditionAssignmentModalMessage: document.getElementById("conditionAssignmentModalMessage"),
    conditionAllocationFields: document.getElementById("conditionAllocationFields"),
    conditionRandomSeed: document.getElementById("conditionRandomSeed"),
    conditionRandomButton: document.getElementById("conditionRandomButton"),
    conditionAssignmentRows: document.getElementById("conditionAssignmentRows"),
    conditionAssignmentSaveButton: document.getElementById("conditionAssignmentSaveButton"),
    slotPanel: document.getElementById("slotPanel"),
    slotList: document.getElementById("slotList"),
    slotFormTitle: document.getElementById("slotFormTitle"),
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
    bulkGradingButton: document.getElementById("bulkGradingButton"),
    backFromGradingButton: document.getElementById("backFromGradingButton"),
    participationHeaderRow: document.getElementById("participationHeaderRow"),
    participationRows: document.getElementById("participationRows"),
    bulkGradingModal: document.getElementById("bulkGradingModal"),
    bulkGradingModalMessage: document.getElementById("bulkGradingModalMessage"),
    bulkGradingOperation: document.getElementById("bulkGradingOperation"),
    bulkSelectAllButton: document.getElementById("bulkSelectAllButton"),
    bulkSelectNoneButton: document.getElementById("bulkSelectNoneButton"),
    bulkSelectedCount: document.getElementById("bulkSelectedCount"),
    bulkParticipationHeaderRow: document.getElementById("bulkParticipationHeaderRow"),
    bulkParticipationRows: document.getElementById("bulkParticipationRows"),
    bulkExecuteButton: document.getElementById("bulkExecuteButton"),
    noShowTitle: document.getElementById("noShowTitle"),
    noShowList: document.getElementById("noShowList"),
};

document.addEventListener("DOMContentLoaded", () => {
    wireEvents();
    dom.participantRandomSeed.value = defaultSeed();
    dom.conditionRandomSeed.value = defaultSeed();
    updateSystemStatus();
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
    dom.bulkGradingButton.addEventListener("click", () => {
        openBulkGradingModal();
    });
    dom.bulkSelectAllButton.addEventListener("click", () => {
        for (const participation of bulkVisibleRows()) {
            state.bulkSelectedIds.add(participation.id);
        }
        renderBulkGradingModal();
    });
    dom.bulkSelectNoneButton.addEventListener("click", () => {
        state.bulkSelectedIds.clear();
        renderBulkGradingModal();
    });
    dom.bulkExecuteButton.addEventListener("click", async () => {
        await executeBulkGradingOperation();
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

    dom.cancelConditionButton.addEventListener("click", () => {
        clearConditionForm();
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

    dom.valueSource.addEventListener("change", () => {
        updateSharedValueVisibility();
    });

    dom.fieldForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const experiment = selectedExperiment();
        if (!experiment) {
            showMessage("Bitte speichern Sie zuerst das Experiment.", "warning");
            return;
        }
        const sharedValue = dom.valueSource.value === "shared" ? dom.sharedValue.value : "";
        await postAction("save_access_field", {
            id: valueOrNull(dom.fieldId.value),
            experimentId: experiment.id,
            conditionId: valueOrNull(dom.fieldConditionId.value),
            label: dom.fieldLabel.value,
            fieldKey: dom.fieldKey.value,
            valueType: dom.valueType.value,
            valueSource: dom.valueSource.value,
            sharedValue,
            sortOrder: dom.fieldSortOrder.value,
            isVisible: dom.fieldIsVisible.checked,
        });
        clearFieldForm();
        await loadDashboard("Zugangsfeld gespeichert.");
    });

    dom.cancelFieldButton.addEventListener("click", () => {
        clearFieldForm();
    });

    dom.managePoolButton.addEventListener("click", () => {
        openPoolModal();
    });
    dom.clearPoolButton.addEventListener("click", async () => {
        await clearAccessPool();
    });
    dom.poolModalConditionId.addEventListener("change", () => {
        clearMessage(dom.poolModalMessage);
        renderPoolSample();
    });
    dom.poolSaveButton.addEventListener("click", async () => {
        await savePoolRows();
    });

    dom.manageStaffValuesButton.addEventListener("click", () => {
        openStaffValueModal();
    });
    dom.staffValueSaveButton.addEventListener("click", async () => {
        await saveStaffEligibilityFieldValues();
    });

    dom.manageParticipantsButton.addEventListener("click", () => {
        openParticipantModal();
    });
    dom.clearParticipantsButton.addEventListener("click", async () => {
        await clearParticipantSelection();
    });

    dom.participantSelectAllButton.addEventListener("click", () => {
        state.participantDraftMode = "all_allowed";
        state.participantDraftEmails = new Set(allowedEmails());
        renderParticipantModal();
        showMessage(`Alle ${state.participantDraftEmails.size} Studierenden ausgewählt.`, "success", dom.participantModalMessage);
    });
    dom.participantRandomButton.addEventListener("click", () => {
        selectRandomParticipants();
    });
    dom.participantSearchInput.addEventListener("input", () => {
        clearMessage(dom.participantModalMessage);
        renderParticipantSuggestions();
    });
    dom.participantSaveButton.addEventListener("click", async () => {
        await saveParticipantSelection();
    });

    dom.manageConditionAssignmentsButton.addEventListener("click", () => {
        openConditionAssignmentModal();
    });
    dom.clearConditionAssignmentsButton.addEventListener("click", async () => {
        await clearConditionAssignments();
    });
    dom.conditionRandomButton.addEventListener("click", () => {
        applyRandomConditionAssignments();
    });
    dom.conditionAssignmentSaveButton.addEventListener("click", async () => {
        await saveConditionAssignments();
    });

    for (const [modal, messageTarget] of [
        [dom.participantModal, dom.participantModalMessage],
        [dom.staffValueModal, dom.staffValueModalMessage],
        [dom.poolModal, dom.poolModalMessage],
        [dom.conditionAssignmentModal, dom.conditionAssignmentModalMessage],
        [dom.bulkGradingModal, dom.bulkGradingModalMessage],
    ]) {
        modal.addEventListener("hidden.bs.modal", () => {
            clearMessage(messageTarget);
        });
    }

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
    renderPoolSection();
    renderParticipantSection();
    renderConditionAssignmentSection();
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
    if (state.selectedExperimentId !== experimentId) {
        state.gradingFilters = {};
        state.gradingSort = { key: "", direction: "asc" };
        state.bulkFilters = {};
        state.bulkSort = { key: "", direction: "asc" };
        state.bulkSelectedIds.clear();
    }
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
        item.className = "list-group-item list-group-item-action";
        item.tabIndex = 0;
        item.setAttribute("role", "button");
        item.addEventListener("click", () => openExperiment(experiment.id));
        item.addEventListener("keydown", (event) => {
            if (event.target !== item) {
                return;
            }
            if (event.key !== "Enter" && event.key !== " ") {
                return;
            }
            event.preventDefault();
            openExperiment(experiment.id);
        });

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
    wrapper.addEventListener("click", (event) => event.stopPropagation());

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
    const experiment = selectedExperiment();
    const hasSavedExperiment = experiment !== null;
    for (const panel of [dom.conditionPanel, dom.accessPanel, dom.participantPanel]) {
        panel.classList.toggle("d-none", !hasSavedExperiment || state.view !== "experiment");
    }
    dom.poolPanel.classList.toggle("d-none", !hasSavedExperiment || state.view !== "experiment" || !hasSavedPoolFields(experiment));
    const showConditionAssignments = hasSavedExperiment
        && state.view === "experiment"
        && experiment.conditionMode === "assigned"
        && (experiment.conditions || []).length > 0;
    dom.conditionAssignmentPanel.classList.toggle("d-none", !showConditionAssignments);
    dom.slotPanel.classList.toggle("d-none", !hasSavedExperiment || state.view !== "experiment" || !experiment.requiresTimeSlot);
}

function setExperimentActionState(isNewExperiment) {
    dom.cancelExperimentButton.classList.toggle("d-none", !isNewExperiment);
    dom.deleteExperimentButton.classList.toggle("d-none", isNewExperiment);
    dom.deleteExperimentButton.disabled = isNewExperiment;
}

function updateSharedValueVisibility() {
    const usesSharedValue = dom.valueSource.value === "shared";
    dom.sharedValueGroup.classList.toggle("d-none", !usesSharedValue);
    dom.sharedValue.disabled = !usesSharedValue;
    if (!usesSharedValue) {
        dom.sharedValue.value = "";
    }
}

function hasSavedPoolFields(experiment) {
    return (experiment?.accessFields || []).some((field) => field.valueSource === "pool");
}

function updateStaffValueSection(experiment) {
    const fields = staffEntryFieldsForExperiment(experiment);
    const emails = effectiveParticipantEmails(experiment);
    const isVisible = state.view === "experiment" && fields.length > 0;
    dom.staffValueSection.classList.toggle("d-none", !isVisible);
    dom.manageStaffValuesButton.disabled = !isVisible || emails.length === 0 || (experiment?.counts?.participations || 0) > 0;
    if (!isVisible) {
        dom.staffValueSummary.textContent = "";
        return;
    }

    const completeness = staffValueCompleteness(experiment, fields, emails);
    const locked = (experiment?.counts?.participations || 0) > 0
        ? " Werte sind gesperrt, weil bereits Zugang geöffnet wurde."
        : "";
    dom.staffValueSummary.textContent = `${completeness.filled}/${completeness.total} Werte vorbereitet.${locked}`;
}

function staffEntryFieldsForExperiment(experiment) {
    return (experiment?.accessFields || []).filter((field) => field.valueSource === "staff_entry" && field.valueType !== "appointment");
}

function staffValueCompleteness(experiment, fields, emails) {
    const eligibilities = eligibilityByEmail(experiment);
    let total = 0;
    let filled = 0;
    for (const email of emails) {
        const eligibility = eligibilities.get(email);
        const values = fieldValueMap(eligibility);
        for (const field of fields) {
            if (!staffFieldAppliesToEligibility(field, eligibility)) {
                continue;
            }
            total++;
            if ((values.get(field.id) || "") !== "") {
                filled++;
            }
        }
    }
    return { filled, total };
}

function staffFieldAppliesToEligibility(field, eligibility) {
    return field.conditionId === null || field.conditionId === (eligibility?.conditionId || null);
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
            dom.conditionFormTitle.textContent = "Bedingung bearbeiten";
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
    updateStaffValueSection(experiment);

    if (!experiment) {
        return;
    }

    for (const field of experiment.accessFields || []) {
        const conditionName = conditionNameById(experiment, field.conditionId) || "experimentweit";
        const item = compactItem(field.label, `${conditionName}, ${field.valueSource}, ${field.valueType}`);
        const edit = smallButton("Bearbeiten", "outline-primary");
        edit.addEventListener("click", () => {
            dom.fieldFormTitle.textContent = "Zugangsdaten bearbeiten";
            dom.fieldId.value = field.id;
            dom.fieldConditionId.value = field.conditionId || "";
            dom.fieldLabel.value = field.label;
            dom.fieldKey.value = field.key;
            dom.valueType.value = field.valueType;
            dom.valueSource.value = field.valueSource;
            dom.sharedValue.value = field.sharedValue || "";
            dom.fieldSortOrder.value = field.sortOrder;
            dom.fieldIsVisible.checked = field.isVisible;
            updateSharedValueVisibility();
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

function renderPoolSection() {
    const experiment = selectedExperiment();
    dom.poolRowList.innerHTML = "";
    if (!experiment || !hasSavedPoolFields(experiment)) {
        dom.poolSummary.textContent = "";
        return;
    }

    const rows = experiment.accessPoolRows || [];
    const total = rows.length;
    const assigned = rows.filter((poolRow) => poolRow.isAssigned).length;
    const available = total - assigned;
    const parts = (experiment.poolCounts || []).map((count) => {
        const conditionName = conditionNameById(experiment, count.conditionId) || "experimentweit";
        return `${conditionName}: ${count.available}/${count.total} frei`;
    });
    dom.poolSummary.textContent = parts.length > 0
        ? `${available}/${total} Pool-Zeilen frei. ${parts.join(" · ")}`
        : "Noch kein Zugangsdaten-Pool bereitgestellt.";
    dom.clearPoolButton.disabled = total === 0 || assigned > 0;
    dom.clearPoolButton.title = assigned > 0
        ? "Zugewiesene Pool-Zeilen können nicht gelöscht werden."
        : "Gesamten Zugangsdaten-Pool löschen";

    for (const poolRow of rows) {
        const conditionName = poolRow.conditionName || "experimentweit";
        const values = (poolRow.values || []).map((value) => `${value.label}: ${value.value}`).join(" · ");
        const assigned = poolRow.isAssigned
            ? `zugewiesen an ${poolRow.assignedStudentEmail || "unbekannt"}`
            : "frei";
        const item = compactItem(`#${poolRow.id} · ${conditionName}`, `${assigned}${values ? " · " + values : ""}`);
        dom.poolRowList.appendChild(item);
    }

    if (rows.length === 0) {
        dom.poolRowList.appendChild(emptyText("Keine Pool-Zeilen vorhanden."));
    }
}

function openPoolModal() {
    const experiment = selectedExperiment();
    if (!experiment) {
        showMessage("Bitte speichern Sie zuerst das Experiment.", "warning");
        return;
    }
    if (!hasSavedPoolFields(experiment)) {
        showMessage("Bitte legen Sie zuerst ein Zugangsfeld mit Quelle Aus Pool zuweisen an.", "warning");
        return;
    }
    clearMessage(dom.poolModalMessage);
    fillConditionSelect(dom.poolModalConditionId, experiment, "Experimentweit");
    configurePoolConditionSelect(experiment);
    dom.poolTable.value = "";
    renderPoolSample();
    bootstrap.Modal.getOrCreateInstance(dom.poolModal).show();
}

function renderPoolSample() {
    const experiment = selectedExperiment();
    if (!experiment) {
        dom.poolSample.textContent = "";
        dom.poolSaveButton.disabled = true;
        return;
    }

    const conditionId = valueOrNull(dom.poolModalConditionId.value);
    if (poolImportRequiresCondition(experiment) && conditionId === null) {
        dom.poolSample.textContent = "Bitte wählen Sie eine Bedingung aus.";
        dom.poolSaveButton.disabled = true;
        return;
    }

    const fields = poolFieldsForCondition(experiment, conditionId);
    if (fields.length === 0) {
        dom.poolSample.textContent = "Für diese Auswahl sind keine Pool-Felder definiert.";
        dom.poolSaveButton.disabled = true;
        return;
    }

    const headers = fields.map((field) => field.key);
    const rowOne = fields.map((field) => samplePoolValue(field, 1));
    const rowTwo = fields.map((field) => samplePoolValue(field, 2));
    dom.poolSample.textContent = [headers, rowOne, rowTwo].map((row) => row.join(",")).join("\n");
    dom.poolSaveButton.disabled = false;
}

function poolFieldsForCondition(experiment, conditionId) {
    return (experiment.accessFields || []).filter((field) => {
        if (field.valueSource !== "pool") {
            return false;
        }
        if (conditionId === null) {
            return field.conditionId === null;
        }
        return field.conditionId === null || field.conditionId === conditionId;
    });
}

function configurePoolConditionSelect(experiment) {
    const emptyOption = dom.poolModalConditionId.querySelector('option[value=""]');
    const hasConditions = experimentHasConditionRows(experiment);
    dom.poolModalConditionGroup.classList.toggle("d-none", !hasConditions);
    dom.poolModalConditionId.disabled = !hasConditions;
    if (!hasConditions) {
        dom.poolModalConditionId.value = "";
    }

    if (!poolImportRequiresCondition(experiment)) {
        if (emptyOption) {
            emptyOption.disabled = false;
            emptyOption.textContent = "Experimentweit";
        }
        dom.poolConditionHint.textContent = "";
        return;
    }

    if (emptyOption) {
        emptyOption.disabled = true;
        emptyOption.textContent = "Experimentweit (nur ohne Bedingungen)";
    }

    if (dom.poolModalConditionId.value === "" && dom.poolModalConditionId.options.length > 1) {
        dom.poolModalConditionId.value = dom.poolModalConditionId.options[1].value;
    }

    dom.poolConditionHint.textContent = "Dieses Experiment verwendet Bedingungen. Pool-Zeilen werden pro Bedingung importiert; die CSV enthält dabei auch experimentweite Pool-Felder wie PID oder Umfrage-Link.";
}

function poolImportRequiresCondition(experiment) {
    return Boolean(experiment) && experiment.conditionMode !== "none" && experimentHasConditionRows(experiment);
}

function experimentHasConditionRows(experiment) {
    return (experiment?.conditions || []).length > 0;
}

function samplePoolValue(field, index) {
    const suffix = String(index).padStart(3, "0");
    if (field.valueType === "url") {
        return `https://example.test/${field.key}/${suffix}`;
    }
    if (field.valueType === "pid") {
        return `P${suffix}`;
    }
    return `${field.key}_${suffix}`;
}

async function savePoolRows() {
    const experiment = selectedExperiment();
    if (!experiment) {
        return;
    }
    if (poolImportRequiresCondition(experiment) && valueOrNull(dom.poolModalConditionId.value) === null) {
        showMessage("Bitte wählen Sie eine Bedingung aus.", "warning", dom.poolModalMessage);
        return;
    }
    const payload = await postAction("import_pool_rows", {
        experimentId: experiment.id,
        conditionId: valueOrNull(dom.poolModalConditionId.value),
        table: dom.poolTable.value,
    }, dom.poolModalMessage);
    bootstrap.Modal.getOrCreateInstance(dom.poolModal).hide();
    dom.poolTable.value = "";
    await loadDashboard(`${payload.imported} Pool-Zeilen importiert.`);
}

async function clearAccessPool() {
    const experiment = selectedExperiment();
    if (!experiment) {
        return;
    }
    if (!window.confirm("Gesamten Zugangsdaten-Pool für dieses Experiment löschen?")) {
        return;
    }
    const payload = await postAction("clear_access_pool_rows", {
        experimentId: experiment.id,
    });
    await loadDashboard(`${payload.deletedCount} Pool-Zeilen gelöscht.`);
}

function openStaffValueModal() {
    const experiment = selectedExperiment();
    if (!experiment) {
        showMessage("Bitte speichern Sie zuerst das Experiment.", "warning");
        return;
    }
    if ((experiment.counts?.participations || 0) > 0) {
        showMessage("Staff-Werte können nicht mehr geändert werden, nachdem Studierende Zugang geöffnet haben.", "warning");
        return;
    }
    if (staffEntryFieldsForExperiment(experiment).length === 0) {
        showMessage("Bitte legen Sie zuerst ein Zugangsfeld mit Quelle Durch Staff eintragen an.", "warning");
        return;
    }
    if (effectiveParticipantEmails(experiment).length === 0) {
        showMessage("Bitte wählen Sie zuerst teilnehmende Studierende aus.", "warning");
        return;
    }

    clearMessage(dom.staffValueModalMessage);
    renderStaffValueModal();
    bootstrap.Modal.getOrCreateInstance(dom.staffValueModal).show();
}

function renderStaffValueModal() {
    const experiment = selectedExperiment();
    dom.staffValueHeaderRow.innerHTML = "";
    dom.staffValueRows.innerHTML = "";
    if (!experiment) {
        return;
    }

    const fields = staffEntryFieldsForExperiment(experiment);
    appendHeaderCell(dom.staffValueHeaderRow, "E-Mail");
    for (const field of fields) {
        appendHeaderCell(dom.staffValueHeaderRow, field.label);
    }

    const eligibilities = eligibilityByEmail(experiment);
    for (const email of effectiveParticipantEmails(experiment)) {
        const eligibility = eligibilities.get(email);
        const values = fieldValueMap(eligibility);
        const row = document.createElement("tr");
        appendTableCell(row, email, "");
        for (const field of fields) {
            const input = document.createElement("input");
            input.className = "form-control form-control-sm";
            input.type = field.valueType === "url" ? "url" : "text";
            input.value = values.get(field.id) || "";
            input.dataset.email = email;
            input.dataset.fieldId = String(field.id);
            if (!staffFieldAppliesToEligibility(field, eligibility)) {
                input.disabled = true;
                input.placeholder = "Nicht für Bedingung";
            }
            appendTableCell(row, input, "");
        }
        dom.staffValueRows.appendChild(row);
    }
}

async function saveStaffEligibilityFieldValues() {
    const experiment = selectedExperiment();
    if (!experiment) {
        return;
    }

    const rows = new Map();
    for (const input of dom.staffValueRows.querySelectorAll("input[data-email][data-field-id]")) {
        if (input.disabled) {
            continue;
        }
        const email = input.dataset.email;
        if (!rows.has(email)) {
            rows.set(email, []);
        }
        rows.get(email).push({
            fieldId: Number(input.dataset.fieldId),
            value: input.value,
        });
    }

    const payload = await postAction("save_staff_eligibility_field_values", {
        experimentId: experiment.id,
        rows: Array.from(rows.entries()).map(([email, values]) => ({ email, values })),
    }, dom.staffValueModalMessage);
    bootstrap.Modal.getOrCreateInstance(dom.staffValueModal).hide();
    await loadDashboard(`${payload.savedCount} Staff-Werte gespeichert.`);
}

function renderParticipantSection() {
    const experiment = selectedExperiment();
    dom.participantList.innerHTML = "";
    if (!experiment) {
        dom.participantSummary.textContent = "";
        return;
    }

    const emails = effectiveParticipantEmails(experiment);
    dom.clearParticipantsButton.disabled = emails.length === 0;
    dom.participantSummary.textContent = experiment.eligibilityMode === "all_allowed"
        ? `Alle ${allowedEmails().length} global zugelassenen Studierenden können teilnehmen.`
        : `${emails.length} Studierende sind für dieses Experiment ausgewählt.`;

    if (emails.length === 0) {
        dom.participantList.appendChild(emptyListGroupItem("Keine Studierenden ausgewählt."));
        return;
    }

    for (const email of emails.slice(0, 12)) {
        const eligibility = eligibilityByEmail(experiment).get(email);
        const condition = eligibility?.conditionName || "keine Bedingung";
        const source = eligibility?.source || (experiment.eligibilityMode === "all_allowed" ? "alle zugelassen" : "manuell");
        dom.participantList.appendChild(listGroupSummaryItem(email, `${condition} · ${source}`));
    }
    if (emails.length > 12) {
        dom.participantList.appendChild(emptyListGroupItem(`${emails.length - 12} weitere Studierende.`));
    }
}

function renderConditionAssignmentSection() {
    const experiment = selectedExperiment();
    dom.conditionAssignmentList.innerHTML = "";
    if (!experiment) {
        dom.conditionAssignmentSummary.textContent = "";
        return;
    }

    if (experiment.conditionMode !== "assigned" || (experiment.conditions || []).length === 0) {
        dom.conditionAssignmentSummary.textContent = "Für dieses Experiment ist keine Bedingungszuweisung erforderlich.";
        return;
    }

    const emails = effectiveParticipantEmails(experiment);
    const eligibilities = eligibilityByEmail(experiment);
    const assignedCount = emails.filter((email) => eligibilities.get(email)?.conditionId).length;
    dom.clearConditionAssignmentsButton.disabled = assignedCount === 0;
    dom.conditionAssignmentSummary.textContent = `${assignedCount}/${emails.length} Studierende haben eine Bedingung.`;

    if (emails.length === 0) {
        dom.conditionAssignmentList.appendChild(emptyListGroupItem("Bitte zuerst teilnehmende Studierende auswählen."));
        return;
    }

    for (const email of emails.slice(0, 12)) {
        const eligibility = eligibilities.get(email);
        dom.conditionAssignmentList.appendChild(listGroupSummaryItem(email, eligibility?.conditionName || "keine Bedingung"));
    }
    if (emails.length > 12) {
        dom.conditionAssignmentList.appendChild(emptyListGroupItem(`${emails.length - 12} weitere Studierende.`));
    }
}

function openParticipantModal() {
    const experiment = selectedExperiment();
    if (!experiment) {
        showMessage("Bitte speichern Sie zuerst das Experiment.", "warning");
        return;
    }
    state.participantDraftMode = experiment.eligibilityMode;
    state.participantDraftEmails = new Set(effectiveParticipantEmails(experiment));
    clearMessage(dom.participantModalMessage);
    dom.participantRandomCount.value = Math.min(Math.max(1, state.participantDraftEmails.size || 1), allowedEmails().length || 1);
    dom.participantRandomSeed.value = dom.participantRandomSeed.value || defaultSeed();
    dom.participantSearchInput.value = "";
    renderParticipantModal();
    bootstrap.Modal.getOrCreateInstance(dom.participantModal).show();
}

function renderParticipantModal() {
    const emails = sortedEmails(Array.from(state.participantDraftEmails));
    dom.participantSelectedCount.textContent = `${emails.length} ausgewählt`;
    dom.participantModalSelectedList.innerHTML = "";

    if (emails.length === 0) {
        dom.participantModalSelectedList.appendChild(emptyListGroupItem("Keine Studierenden ausgewählt."));
    }

    for (const email of emails) {
        const item = document.createElement("div");
        item.className = "list-group-item d-flex justify-content-between gap-3 align-items-center";
        const strong = document.createElement("strong");
        strong.textContent = email;
        const remove = smallButton("Entfernen", "outline-danger");
        remove.addEventListener("click", () => {
            clearMessage(dom.participantModalMessage);
            state.participantDraftMode = "selected";
            state.participantDraftEmails.delete(email);
            renderParticipantModal();
        });
        item.append(strong, remove);
        dom.participantModalSelectedList.appendChild(item);
    }

    renderParticipantSuggestions();
}

function renderParticipantSuggestions() {
    const query = dom.participantSearchInput.value.trim().toLowerCase();
    dom.participantSuggestionList.innerHTML = "";
    if (query === "") {
        return;
    }

    const suggestions = allowedEmails()
        .filter((email) => !state.participantDraftEmails.has(email))
        .filter((email) => email.toLowerCase().includes(query))
        .slice(0, 8);

    for (const email of suggestions) {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "list-group-item list-group-item-action";
        button.textContent = email;
        button.addEventListener("click", () => {
            clearMessage(dom.participantModalMessage);
            state.participantDraftMode = "selected";
            state.participantDraftEmails.add(email);
            dom.participantSearchInput.value = "";
            renderParticipantModal();
        });
        dom.participantSuggestionList.appendChild(button);
    }
}

function selectRandomParticipants() {
    const emails = allowedEmails();
    const requestedCount = Number(dom.participantRandomCount.value);
    if (!Number.isInteger(requestedCount) || requestedCount < 1 || requestedCount > emails.length) {
        showMessage(`Bitte eine Anzahl zwischen 1 und ${emails.length} eingeben.`, "warning", dom.participantModalMessage);
        return;
    }
    clearMessage(dom.participantModalMessage);
    state.participantDraftMode = "selected";
    state.participantDraftEmails = new Set(deterministicEmailOrder(emails, dom.participantRandomSeed.value || defaultSeed()).slice(0, requestedCount));
    renderParticipantModal();
    showMessage(`${requestedCount} Studierende ausgewählt.`, "success", dom.participantModalMessage);
}

async function saveParticipantSelection() {
    const experiment = selectedExperiment();
    if (!experiment) {
        return;
    }
    const payload = await postAction("save_eligibility_selection", {
        experimentId: experiment.id,
        mode: state.participantDraftMode,
        emails: Array.from(state.participantDraftEmails),
    }, dom.participantModalMessage);
    bootstrap.Modal.getOrCreateInstance(dom.participantModal).hide();
    await loadDashboard(`${payload.selectedCount} Studierende gespeichert.`);
}

async function clearParticipantSelection() {
    const experiment = selectedExperiment();
    if (!experiment) {
        return;
    }
    if (!window.confirm("Auswahl der teilnehmenden Studierenden für dieses Experiment löschen?")) {
        return;
    }
    await postAction("clear_eligibility_selection", {
        experimentId: experiment.id,
    });
    await loadDashboard("Auswahl der teilnehmenden Studierenden gelöscht.");
}

function openConditionAssignmentModal() {
    const experiment = selectedExperiment();
    if (!experiment) {
        showMessage("Bitte speichern Sie zuerst das Experiment.", "warning");
        return;
    }
    if (experiment.conditionMode !== "assigned" || (experiment.conditions || []).length === 0) {
        showMessage("Bedingungszuweisung ist nur im Modus Zugewiesen verfügbar.", "warning");
        return;
    }

    const eligibilities = eligibilityByEmail(experiment);
    clearMessage(dom.conditionAssignmentModalMessage);
    state.conditionAssignmentDraft = new Map();
    for (const email of effectiveParticipantEmails(experiment)) {
        state.conditionAssignmentDraft.set(email, eligibilities.get(email)?.conditionId || "");
    }
    state.conditionAssignmentSource = "manual";
    dom.conditionRandomSeed.value = dom.conditionRandomSeed.value || defaultSeed();
    renderConditionAssignmentModal();
    bootstrap.Modal.getOrCreateInstance(dom.conditionAssignmentModal).show();
}

function renderConditionAssignmentModal() {
    const experiment = selectedExperiment();
    dom.conditionAllocationFields.innerHTML = "";
    dom.conditionAssignmentRows.innerHTML = "";
    if (!experiment) {
        return;
    }

    const defaultPercentage = experiment.conditions.length > 0 ? Math.floor(100 / experiment.conditions.length) : 0;
    let remainder = 100 - (defaultPercentage * experiment.conditions.length);
    for (const condition of experiment.conditions) {
        const wrapper = document.createElement("label");
        wrapper.className = "input-group input-group-sm";
        const percentage = defaultPercentage + (remainder > 0 ? 1 : 0);
        remainder = Math.max(0, remainder - 1);
        wrapper.innerHTML = `
            <span class="input-group-text">${escapeHtml(condition.name)}</span>
            <input class="form-control" type="number" min="0" max="100" step="0.01" value="${percentage}" data-condition-id="${condition.id}">
            <span class="input-group-text">%</span>
        `;
        dom.conditionAllocationFields.appendChild(wrapper);
    }

    for (const email of sortedEmails(Array.from(state.conditionAssignmentDraft.keys()))) {
        const row = document.createElement("tr");
        const emailCell = document.createElement("td");
        emailCell.textContent = email;
        const conditionCell = document.createElement("td");
        const select = document.createElement("select");
        select.className = "form-select form-select-sm";
        select.dataset.email = email;
        select.appendChild(new Option("Bitte wählen", ""));
        for (const condition of experiment.conditions) {
            select.appendChild(new Option(condition.name, condition.id));
        }
        select.value = String(state.conditionAssignmentDraft.get(email) || "");
        select.addEventListener("change", () => {
            clearMessage(dom.conditionAssignmentModalMessage);
            state.conditionAssignmentSource = "manual";
            state.conditionAssignmentDraft.set(email, valueOrNull(select.value) || "");
        });
        conditionCell.appendChild(select);
        row.append(emailCell, conditionCell);
        dom.conditionAssignmentRows.appendChild(row);
    }
}

function applyRandomConditionAssignments() {
    const experiment = selectedExperiment();
    if (!experiment) {
        return;
    }
    const allocationInputs = Array.from(dom.conditionAllocationFields.querySelectorAll("input[data-condition-id]"));
    const allocations = allocationInputs.map((input) => ({
        conditionId: Number(input.dataset.conditionId),
        percentage: Number(input.value),
    }));
    const sum = allocations.reduce((total, allocation) => total + allocation.percentage, 0);
    if (allocations.some((allocation) => !Number.isFinite(allocation.percentage) || allocation.percentage < 0) || Math.abs(sum - 100) > 0.001) {
        showMessage("Die Prozentwerte müssen zusammen 100 ergeben.", "warning", dom.conditionAssignmentModalMessage);
        return;
    }

    clearMessage(dom.conditionAssignmentModalMessage);
    const emails = deterministicEmailOrder(Array.from(state.conditionAssignmentDraft.keys()), dom.conditionRandomSeed.value || defaultSeed());
    const counts = allocationCountsForTotal(allocations, emails.length);
    let emailIndex = 0;
    for (const allocation of allocations) {
        const count = counts.get(allocation.conditionId) || 0;
        for (let index = 0; index < count && emailIndex < emails.length; index++) {
            state.conditionAssignmentDraft.set(emails[emailIndex], allocation.conditionId);
            emailIndex++;
        }
    }
    state.conditionAssignmentSource = "random";
    renderConditionAssignmentModal();
    showMessage("Bedingungen randomisiert.", "success", dom.conditionAssignmentModalMessage);
}

async function saveConditionAssignments() {
    const experiment = selectedExperiment();
    if (!experiment) {
        return;
    }
    const assignments = Array.from(state.conditionAssignmentDraft.entries()).map(([email, conditionId]) => ({
        email,
        conditionId: valueOrNull(conditionId),
    }));
    if (assignments.some((assignment) => assignment.conditionId === null)) {
        showMessage("Bitte für alle Studierenden eine Bedingung auswählen.", "warning", dom.conditionAssignmentModalMessage);
        return;
    }
    const payload = await postAction("save_condition_assignments", {
        experimentId: experiment.id,
        source: state.conditionAssignmentSource,
        assignments,
    }, dom.conditionAssignmentModalMessage);
    bootstrap.Modal.getOrCreateInstance(dom.conditionAssignmentModal).hide();
    await loadDashboard(`${payload.assignedCount} Bedingungszuweisungen gespeichert.`);
}

async function clearConditionAssignments() {
    const experiment = selectedExperiment();
    if (!experiment) {
        return;
    }
    if (!window.confirm("Alle Bedingungszuweisungen für dieses Experiment aufheben?")) {
        return;
    }
    await postAction("clear_condition_assignments", {
        experimentId: experiment.id,
    });
    await loadDashboard("Bedingungszuweisung aufgehoben.");
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
            dom.slotFormTitle.textContent = "Zeitslot bearbeiten";
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
    dom.participationHeaderRow.innerHTML = "";
    dom.noShowList.innerHTML = "";
    dom.gradingTitle.textContent = experiment ? `${experiment.name} Anrechnung` : "Anrechnung";
    dom.noShowTitle.textContent = "No-Shows";
    dom.bulkGradingButton.disabled = true;

    if (state.view !== "grading" || !experiment) {
        return;
    }

    const columns = gradingColumns(experiment);
    const dataColumns = columns.filter((column) => column.filterable !== false);
    const rows = participationRowsForExperiment(experiment);
    const visibleRows = filteredAndSortedRows(rows, dataColumns, state.gradingFilters, state.gradingSort);
    dom.bulkGradingButton.disabled = rows.length === 0;
    for (const column of columns) {
        appendDataHeaderCell(dom.participationHeaderRow, column, rows, state.gradingFilters, state.gradingSort, () => {
            renderGradingRows();
        });
    }

    renderGradingRows(experiment, columns, rows, visibleRows);
    renderNoShows(experiment, rows);
}

function renderGradingRows(
    experiment = selectedExperiment(),
    columns = experiment ? gradingColumns(experiment) : [],
    rows = experiment ? participationRowsForExperiment(experiment) : [],
    visibleRows = filteredAndSortedRows(rows, columns.filter((column) => column.filterable !== false), state.gradingFilters, state.gradingSort)
) {
    dom.participationRows.innerHTML = "";
    if (!experiment) {
        return;
    }

    for (const participation of visibleRows) {
        const row = document.createElement("tr");
        for (const column of columns) {
            appendTableCell(row, column.render(participation), column.cellClass || "");
        }
        dom.participationRows.appendChild(row);
    }

    if (visibleRows.length === 0) {
        const row = document.createElement("tr");
        const cell = document.createElement("td");
        cell.colSpan = columns.length;
        cell.className = "text-secondary py-3";
        cell.textContent = rows.length === 0
            ? "Keine Zuweisungen für dieses Experiment vorhanden."
            : "Keine Zuweisungen entsprechen den aktuellen Filtern.";
        row.appendChild(cell);
        dom.participationRows.appendChild(row);
    }
}

function participationRowsForExperiment(experiment) {
    return (state.dashboard.participations || []).filter((participation) => {
        return participation.experimentId === experiment.id;
    });
}

function gradingColumns(experiment, options = {}) {
    const includeActions = options.includeActions !== false;
    const columns = [
        {
            key: "email",
            title: "E-Mail",
            getText: (participation) => participation.email,
            render: (participation) => participation.email,
        },
    ];

    if (experiment.conditionMode !== "none" && (experiment.conditions || []).length > 0) {
        columns.push({
            key: "condition",
            title: "Bedingung",
            getText: (participation) => participation.conditionName || "-",
            render: (participation) => participation.conditionName || "-",
        });
    }

    columns.push({
        key: "assignedAt",
        title: "Zugang geöffnet",
        cellClass: "text-nowrap",
        getText: (participation) => formatDateTime(participation.assignedAt),
        render: (participation) => formatDateTime(participation.assignedAt),
    });

    if (experiment.requiresTimeSlot) {
        columns.push({
            key: "slot",
            title: "Zeitslot",
            getText: (participation) => participation.slotLabel || "-",
            render: (participation) => participation.slotLabel || "-",
        });
    }

    if (hasGradingAccessFields(experiment)) {
        columns.push({
            key: "access",
            title: "Zugangsdaten",
            getText: (participation) => gradingAccessText(participation),
            render: (participation) => gradingAccessDisplay(participation),
        });
    }

    if (hasAppointmentFlow(experiment)) {
        columns.push({
            key: "appointment",
            title: "Uhrzeit",
            getText: (participation) => participation.appointmentText || "",
            render: (participation) => appointmentEditor(participation),
        });
    }

    columns.push({
        key: "confirmed",
        title: "Angerechnet",
        getText: (participation) => participation.confirmed ? "Ja" : "Nein",
        render: (participation) => participation.confirmed ? "Ja" : "Nein",
    });

    if (includeActions) {
        columns.push({
            key: "actions",
            filterable: false,
            sortable: false,
            title: "Aktionen",
            headerClass: "text-end",
            cellClass: "text-end",
            getText: () => "",
            render: (participation) => gradingActions(participation),
        });
    }

    return columns;
}

function gradingAccessText(participation) {
    return (participation.accessItems || [])
        .filter((item) => item.valueType !== "appointment" && item.value !== null && item.value !== undefined && String(item.value) !== "")
        .map((item) => `${item.label}: ${item.value}`)
        .join(" | ");
}

function appendDataHeaderCell(row, column, rows, filters, sort, onChange, options = {}) {
    const header = document.createElement("th");
    if (column.headerClass) {
        header.className = column.headerClass;
    }
    if (column.filterable === false) {
        header.textContent = column.title;
        row.appendChild(header);
        return;
    }
    header.appendChild(dataFilterDropdown(column, rows, filters, sort, onChange, options));
    row.appendChild(header);
}

function dataFilterDropdown(column, rows, filters, sort, onChange, options = {}) {
    const wrapper = document.createElement("div");
    wrapper.className = "dropdown data-filter";
    const button = document.createElement("button");
    button.type = "button";
    button.className = "btn btn-sm dropdown-toggle data-filter-button";
    button.setAttribute("data-bs-toggle", "dropdown");
    button.setAttribute("data-bs-auto-close", "outside");
    button.setAttribute("data-bs-boundary", "viewport");
    button.setAttribute("aria-expanded", "false");
    button.textContent = column.title;
    updateDataFilterButtonState(button, column, filters, sort);

    const menu = document.createElement("div");
    menu.className = "dropdown-menu data-filter-menu p-3";
    menu.addEventListener("click", (event) => event.stopPropagation());

    const sortGroup = document.createElement("div");
    sortGroup.className = "button-row mb-2";
    const ascending = smallButton("A-Z", sort.key === column.key && sort.direction === "asc" ? "primary" : "outline-secondary");
    ascending.addEventListener("click", () => {
        sort.key = column.key;
        sort.direction = "asc";
        updateDataFilterButtonState(button, column, filters, sort);
        onChange();
    });
    const descending = smallButton("Z-A", sort.key === column.key && sort.direction === "desc" ? "primary" : "outline-secondary");
    descending.addEventListener("click", () => {
        sort.key = column.key;
        sort.direction = "desc";
        updateDataFilterButtonState(button, column, filters, sort);
        onChange();
    });
    sortGroup.append(ascending, descending);
    menu.appendChild(sortGroup);

    const search = document.createElement("input");
    search.type = "search";
    search.className = "form-control form-control-sm mb-2";
    search.placeholder = "Suchen";
    search.value = filters[column.key]?.search || "";
    search.addEventListener("keydown", (event) => {
        event.stopPropagation();
    });
    search.addEventListener("input", () => {
        setFilterSearch(filters, column.key, search.value);
        if (options.mode === "select") {
            addUniqueBulkCandidate(rows, options.columns || [column], filters, sort);
        }
        renderFilterValueList(valueList, column, rows, filters, onChange, options);
        updateDataFilterButtonState(button, column, filters, sort);
        onChange();
    });
    menu.appendChild(search);

    const valueActions = document.createElement("div");
    valueActions.className = "button-row mb-2";
    const selectAll = smallButton("Alle", "outline-secondary");
    selectAll.addEventListener("click", () => {
        setFilterValues(filters, column.key, null);
        renderFilterValueList(valueList, column, rows, filters, onChange, options);
        updateDataFilterButtonState(button, column, filters, sort);
        onChange();
    });
    const selectNone = smallButton("Keine", "outline-secondary");
    selectNone.addEventListener("click", () => {
        setFilterValues(filters, column.key, []);
        renderFilterValueList(valueList, column, rows, filters, onChange, options);
        updateDataFilterButtonState(button, column, filters, sort);
        onChange();
    });
    const clear = smallButton("Zurücksetzen", "outline-secondary");
    clear.addEventListener("click", () => {
        delete filters[column.key];
        if (sort.key === column.key) {
            sort.key = "";
            sort.direction = "asc";
        }
        search.value = "";
        renderFilterValueList(valueList, column, rows, filters, onChange, options);
        updateDataFilterButtonState(button, column, filters, sort);
        onChange();
    });
    valueActions.append(selectAll, selectNone, clear);
    menu.appendChild(valueActions);

    const valueList = document.createElement("div");
    valueList.className = "data-filter-values";
    renderFilterValueList(valueList, column, rows, filters, onChange, options);
    menu.appendChild(valueList);

    wrapper.append(button, menu);
    bootstrap.Dropdown.getOrCreateInstance(button, {
        popperConfig(defaultConfig) {
            return {
                ...defaultConfig,
                strategy: "fixed",
            };
        },
    });
    return wrapper;
}

function updateDataFilterButtonState(button, column, filters, sort) {
    const active = filterIsActive(filters[column.key]) || sort.key === column.key;
    button.classList.toggle("btn-primary", active);
    button.classList.toggle("btn-outline-secondary", !active);
}

function renderFilterValueList(valueList, column, rows, filters, onChange, options = {}) {
    valueList.innerHTML = "";
    const allValues = distinctColumnValues(rows, column);
    const search = (filters[column.key]?.search || "").toLowerCase();
    const values = allValues.filter((value) => search === "" || value.toLowerCase().includes(search));
    const selectedValues = selectedFilterValues(filters[column.key], allValues);
    for (const value of values) {
        const item = document.createElement("label");
        item.className = "form-check data-filter-value";
        const checkbox = document.createElement("input");
        checkbox.type = "checkbox";
        checkbox.className = "form-check-input";
        checkbox.checked = options.mode === "select"
            ? bulkColumnValueIsSelected(rows, column, value)
            : selectedValues.has(value);
        checkbox.addEventListener("change", () => {
            if (options.mode === "select") {
                addBulkRowsByColumnValue(rows, column, value);
                onChange();
                renderFilterValueList(valueList, column, rows, filters, onChange, options);
                return;
            }
            const nextValues = new Set(selectedValues);
            if (checkbox.checked) {
                nextValues.add(value);
            } else {
                nextValues.delete(value);
            }
            setFilterValues(filters, column.key, Array.from(nextValues));
            onChange();
        });
        const text = document.createElement("span");
        text.className = "form-check-label";
        text.textContent = value || "-";
        item.append(checkbox, text);
        valueList.appendChild(item);
    }
    if (values.length === 0) {
        const empty = document.createElement("div");
        empty.className = "text-secondary small";
        empty.textContent = "Keine Werte";
        valueList.appendChild(empty);
    }
}

function addUniqueBulkCandidate(rows, columns, filters, sort) {
    const candidates = filteredAndSortedRows(rows, columns, filters, sort);
    if (candidates.length === 1) {
        state.bulkSelectedIds.add(candidates[0].id);
    }
}

function addBulkRowsByColumnValue(rows, column, value) {
    for (const row of rows) {
        if (columnText(column, row) === value) {
            state.bulkSelectedIds.add(row.id);
        }
    }
}

function bulkColumnValueIsSelected(rows, column, value) {
    const matchingRows = rows.filter((row) => columnText(column, row) === value);
    return matchingRows.length > 0 && matchingRows.every((row) => state.bulkSelectedIds.has(row.id));
}

function filterIsActive(filter) {
    return Boolean(filter && ((filter.search || "") !== "" || Array.isArray(filter.values)));
}

function distinctColumnValues(rows, column) {
    return Array.from(new Set(rows.map((row) => columnText(column, row))))
        .sort((left, right) => left.localeCompare(right, "de", { numeric: true, sensitivity: "base" }));
}

function selectedFilterValues(filter, allValues) {
    if (!filter || !Array.isArray(filter.values)) {
        return new Set(allValues);
    }
    return new Set(filter.values);
}

function setFilterSearch(filters, key, value) {
    filters[key] = filters[key] || {};
    filters[key].search = value.trim();
    if (filters[key].search === "" && !Array.isArray(filters[key].values)) {
        delete filters[key];
    }
}

function setFilterValues(filters, key, values) {
    filters[key] = filters[key] || {};
    if (values === null) {
        delete filters[key].values;
    } else {
        filters[key].values = values;
    }
    if ((filters[key].search || "") === "" && !Array.isArray(filters[key].values)) {
        delete filters[key];
    }
}

function filteredAndSortedRows(rows, columns, filters, sort) {
    const columnByKey = new Map(columns.map((column) => [column.key, column]));
    const filtered = rows.filter((row) => {
        for (const column of columns) {
            const filter = filters[column.key];
            if (!filter) {
                continue;
            }
            const text = columnText(column, row);
            if ((filter.search || "") !== "" && !text.toLowerCase().includes(filter.search.toLowerCase())) {
                return false;
            }
            if (Array.isArray(filter.values) && !filter.values.includes(text)) {
                return false;
            }
        }
        return true;
    });

    const sortColumn = columnByKey.get(sort.key);
    if (!sortColumn) {
        return filtered;
    }

    return filtered.slice().sort((left, right) => {
        const comparison = columnText(sortColumn, left).localeCompare(
            columnText(sortColumn, right),
            "de",
            { numeric: true, sensitivity: "base" }
        );
        return sort.direction === "desc" ? -comparison : comparison;
    });
}

function columnText(column, row) {
    return String(column.getText(row) ?? "");
}

function cloneFilterState(filters) {
    const cloned = {};
    for (const [key, filter] of Object.entries(filters)) {
        cloned[key] = {
            search: filter.search || "",
        };
        if (Array.isArray(filter.values)) {
            cloned[key].values = filter.values.slice();
        }
    }
    return cloned;
}

function bulkVisibleRows() {
    const experiment = selectedExperiment();
    if (!experiment) {
        return [];
    }
    const columns = gradingColumns(experiment, { includeActions: false });
    return bulkDisplayedRows(participationRowsForExperiment(experiment), columns);
}

function bulkDisplayedRows(rows, columns) {
    const candidateRows = filteredAndSortedRows(rows, columns, state.bulkFilters, state.bulkSort);
    const displayed = new Map();
    for (const row of candidateRows) {
        displayed.set(row.id, row);
    }
    for (const row of rows) {
        if (state.bulkSelectedIds.has(row.id)) {
            displayed.set(row.id, row);
        }
    }
    return filteredAndSortedRows(Array.from(displayed.values()), columns, {}, state.bulkSort);
}

function openBulkGradingModal() {
    const experiment = selectedExperiment();
    if (!experiment) {
        return;
    }
    state.bulkFilters = cloneFilterState(state.gradingFilters);
    state.bulkSort = { ...state.gradingSort };
    state.bulkSelectedIds = new Set();
    dom.bulkGradingOperation.value = "confirm";
    clearMessage(dom.bulkGradingModalMessage);
    renderBulkGradingModal();
    bootstrap.Modal.getOrCreateInstance(dom.bulkGradingModal).show();
}

function renderBulkGradingModal() {
    const experiment = selectedExperiment();
    dom.bulkParticipationHeaderRow.innerHTML = "";
    dom.bulkParticipationRows.innerHTML = "";
    if (!experiment) {
        dom.bulkSelectedCount.textContent = "0 ausgewählt";
        return;
    }

    const columns = gradingColumns(experiment, { includeActions: false });
    const rows = participationRowsForExperiment(experiment);
    const visibleRows = bulkDisplayedRows(rows, columns);

    const checkboxHeader = document.createElement("th");
    checkboxHeader.className = "bulk-check-column";
    checkboxHeader.textContent = "";
    dom.bulkParticipationHeaderRow.appendChild(checkboxHeader);
    for (const column of columns) {
        appendDataHeaderCell(dom.bulkParticipationHeaderRow, column, rows, state.bulkFilters, state.bulkSort, () => {
            renderBulkGradingRows();
        }, { mode: "select", columns });
    }

    renderBulkGradingRows(experiment, columns, rows, visibleRows);
}

function renderBulkGradingRows(
    experiment = selectedExperiment(),
    columns = experiment ? gradingColumns(experiment, { includeActions: false }) : [],
    rows = experiment ? participationRowsForExperiment(experiment) : [],
    visibleRows = bulkDisplayedRows(rows, columns)
) {
    dom.bulkParticipationRows.innerHTML = "";
    if (!experiment) {
        dom.bulkSelectedCount.textContent = "0 ausgewählt";
        return;
    }

    for (const participation of visibleRows) {
        const row = document.createElement("tr");
        const checkboxCell = document.createElement("td");
        checkboxCell.className = "bulk-check-column";
        const checkbox = document.createElement("input");
        checkbox.type = "checkbox";
        checkbox.className = "form-check-input";
        checkbox.checked = state.bulkSelectedIds.has(participation.id);
        checkbox.addEventListener("change", () => {
            if (checkbox.checked) {
                state.bulkSelectedIds.add(participation.id);
            } else {
                state.bulkSelectedIds.delete(participation.id);
            }
            renderBulkSelectionCount();
        });
        checkboxCell.appendChild(checkbox);
        row.appendChild(checkboxCell);
        for (const column of columns) {
            appendTableCell(row, bulkColumnDisplay(column, participation), column.cellClass || "");
        }
        dom.bulkParticipationRows.appendChild(row);
    }

    if (visibleRows.length === 0) {
        const row = document.createElement("tr");
        const cell = document.createElement("td");
        cell.colSpan = columns.length + 1;
        cell.className = "text-secondary py-3";
        cell.textContent = rows.length === 0
            ? "Keine Zuweisungen für dieses Experiment vorhanden."
            : "Keine Zuweisungen entsprechen den aktuellen Filtern.";
        row.appendChild(cell);
        dom.bulkParticipationRows.appendChild(row);
    }

    renderBulkSelectionCount();
}

function renderBulkSelectionCount() {
    dom.bulkSelectedCount.textContent = `${state.bulkSelectedIds.size} ausgewählt`;
    dom.bulkExecuteButton.disabled = state.bulkSelectedIds.size === 0;
}

function bulkColumnDisplay(column, participation) {
    if (column.key === "access") {
        return gradingAccessDisplay(participation);
    }
    const value = columnText(column, participation);
    const wrapper = document.createElement("span");
    wrapper.className = "bulk-cell-text";
    wrapper.textContent = value || "-";
    wrapper.title = value;
    return wrapper;
}

async function executeBulkGradingOperation() {
    const experiment = selectedExperiment();
    if (!experiment) {
        return;
    }
    const participationIds = Array.from(state.bulkSelectedIds);
    if (participationIds.length === 0) {
        showMessage("Bitte wählen Sie mindestens eine Zuweisung aus.", "warning", dom.bulkGradingModalMessage);
        return;
    }
    const operation = dom.bulkGradingOperation.value;
    if (operation === "reset" && !window.confirm(`${participationIds.length} Zuweisungen zurücksetzen und Zugangsdaten freigeben?`)) {
        return;
    }
    const payload = await postAction("bulk_grading_operation", {
        experimentId: experiment.id,
        operation,
        participationIds,
    }, dom.bulkGradingModalMessage);
    bootstrap.Modal.getOrCreateInstance(dom.bulkGradingModal).hide();
    state.bulkSelectedIds.clear();
    await loadDashboard(`${payload.affectedCount} Studierende ${bulkOperationResultLabel(operation)}.`);
}

function bulkOperationResultLabel(operation) {
    if (operation === "confirm") {
        return "angerechnet";
    }
    if (operation === "unconfirm") {
        return "aus der Anrechnung entfernt";
    }
    return "zurückgesetzt";
}

function hasGradingAccessFields(experiment) {
    return (experiment.accessFields || []).some((field) => field.isVisible && field.valueType !== "appointment");
}

function hasAppointmentFlow(experiment) {
    return experiment.requiresTimeSlot || (experiment.accessFields || []).some((field) => field.valueType === "appointment");
}

function appendTableCell(row, content, className) {
    const cell = document.createElement("td");
    if (className) {
        cell.className = className;
    }
    if (content instanceof Node) {
        cell.appendChild(content);
    } else {
        cell.textContent = content;
    }
    row.appendChild(cell);
}

function appendHeaderCell(row, content) {
    const cell = document.createElement("th");
    cell.textContent = content;
    row.appendChild(cell);
}

function appointmentEditor(participation) {
    const appointmentGroup = document.createElement("div");
    appointmentGroup.className = "input-group input-group-sm grading-appointment-editor";
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
    return appointmentGroup;
}

function gradingActions(participation) {
    const actions = document.createElement("div");
    actions.className = "button-row justify-content-end";
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
    actions.append(confirm, reset);
    return actions;
}

function gradingAccessDisplay(participation) {
    const items = (participation.accessItems || []).filter((item) => {
        return item.valueType !== "appointment" && item.value !== null && item.value !== undefined && String(item.value) !== "";
    });
    const wrapper = document.createElement("div");
    wrapper.className = "grading-access-list";

    if (items.length === 0) {
        wrapper.appendChild(emptyText("Keine Zugangsdaten."));
        return wrapper;
    }

    for (const item of items) {
        if (item.valueType === "url") {
            const link = document.createElement("a");
            link.className = "btn btn-outline-primary btn-sm grading-access-link";
            link.href = item.value;
            link.target = "_blank";
            link.rel = "noopener noreferrer";
            link.title = `${item.label || "Link"} öffnen`;
            link.textContent = item.label || "Link";
            wrapper.appendChild(link);
            continue;
        }

        const value = document.createElement("span");
        value.className = "grading-access-value";
        value.title = `${item.label}: ${item.value}`;
        const label = document.createElement("strong");
        label.textContent = `${item.label}:`;
        const text = document.createElement("span");
        text.textContent = item.value;
        value.append(label, text);
        wrapper.appendChild(value);
    }

    return wrapper;
}

function renderNoShows(experiment, participations) {
    const noShows = noShowStudents(experiment, participations);
    dom.noShowTitle.textContent = `No-Shows (${noShows.length})`;

    if (noShows.length === 0) {
        dom.noShowList.appendChild(emptyListGroupItem("Keine No-Shows vorhanden."));
        return;
    }

    for (const noShow of noShows) {
        dom.noShowList.appendChild(listGroupSummaryItem(noShow.email, noShow.meta));
    }
}

function noShowStudents(experiment, participations) {
    const openedEmails = new Set(participations.map((participation) => participation.email));
    const eligibilities = eligibilityByEmail(experiment);
    return effectiveParticipantEmails(experiment)
        .filter((email) => !openedEmails.has(email))
        .map((email) => {
            const eligibility = eligibilities.get(email) || null;
            return {
                email,
                meta: noShowMeta(experiment, eligibility),
            };
        });
}

function noShowMeta(experiment, eligibility) {
    const parts = [];
    if (experiment.conditionMode !== "none" && (experiment.conditions || []).length > 0) {
        parts.push(eligibility?.conditionName ? `Bedingung: ${eligibility.conditionName}` : "keine Bedingung");
    }
    parts.push(eligibilitySourceLabel(eligibility?.source || (experiment.eligibilityMode === "all_allowed" ? "all_allowed" : "manual")));
    if (eligibility?.createdAt) {
        parts.push(`seit ${formatDateTime(eligibility.createdAt)}`);
    }
    return parts.join(" · ");
}

function eligibilitySourceLabel(source) {
    if (source === "all_allowed") {
        return "alle zugelassen";
    }
    if (source === "random") {
        return "randomisiert";
    }
    if (source === "system") {
        return "system";
    }
    return "manuell";
}

function fieldValueMap(record) {
    const values = new Map();
    for (const fieldValue of record?.fieldValues || []) {
        values.set(fieldValue.fieldId, fieldValue.value || "");
    }
    return values;
}

function selectedExperiment() {
    return (state.dashboard?.experiments || []).find((experiment) => experiment.id === state.selectedExperimentId) || null;
}

function allowedEmails() {
    return sortedEmails((state.dashboard?.allowedStudents || []).map((student) => student.email));
}

function effectiveParticipantEmails(experiment) {
    if (!experiment) {
        return [];
    }
    if (experiment.eligibilityMode === "all_allowed") {
        return allowedEmails();
    }
    return sortedEmails((experiment.eligibilities || []).map((eligibility) => eligibility.email));
}

function eligibilityByEmail(experiment) {
    const byEmail = new Map();
    for (const eligibility of experiment?.eligibilities || []) {
        byEmail.set(eligibility.email, eligibility);
    }
    return byEmail;
}

function sortedEmails(emails) {
    return [...emails].sort((left, right) => left.localeCompare(right));
}

function deterministicEmailOrder(emails, seed) {
    return [...emails].sort((left, right) => {
        const score = seededScore(`${seed}|${left}`) - seededScore(`${seed}|${right}`);
        return score !== 0 ? score : left.localeCompare(right);
    });
}

function seededScore(value) {
    let hash = 2166136261;
    for (let index = 0; index < value.length; index++) {
        hash ^= value.charCodeAt(index);
        hash = Math.imul(hash, 16777619);
    }
    return hash >>> 0;
}

function allocationCountsForTotal(allocations, total) {
    const counts = new Map();
    const remainders = [];
    let assigned = 0;
    for (const allocation of allocations) {
        const exact = (allocation.percentage / 100) * total;
        const count = Math.floor(exact);
        counts.set(allocation.conditionId, count);
        remainders.push({
            conditionId: allocation.conditionId,
            remainder: exact - count,
        });
        assigned += count;
    }
    remainders.sort((left, right) => right.remainder - left.remainder || left.conditionId - right.conditionId);
    let remaining = total - assigned;
    for (const item of remainders) {
        if (remaining <= 0) {
            break;
        }
        counts.set(item.conditionId, (counts.get(item.conditionId) || 0) + 1);
        remaining--;
    }
    return counts;
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
    dom.poolTable.value = "";
}

function clearConditionForm() {
    dom.conditionFormTitle.textContent = "Neue Bedingung";
    dom.conditionId.value = "";
    dom.conditionName.value = "";
    dom.conditionSortOrder.value = "0";
}

function clearFieldForm() {
    dom.fieldFormTitle.textContent = "Neue Zugangsdaten";
    dom.fieldId.value = "";
    dom.fieldConditionId.value = "";
    dom.fieldLabel.value = "";
    dom.fieldKey.value = "";
    dom.valueType.value = "text";
    dom.valueSource.value = "shared";
    dom.sharedValue.value = "";
    dom.fieldSortOrder.value = "0";
    dom.fieldIsVisible.checked = true;
    updateSharedValueVisibility();
}

function clearSlotForm() {
    dom.slotFormTitle.textContent = "Neuer Zeitslot";
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

function listGroupSummaryItem(title, meta) {
    const item = document.createElement("div");
    item.className = "list-group-item";
    const strong = document.createElement("strong");
    strong.textContent = title;
    const detail = document.createElement("div");
    detail.className = "small text-secondary mt-1";
    detail.textContent = meta;
    item.append(strong, detail);
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

async function postAction(action, payload, messageTarget = dom.messageArea) {
    clearMessage(messageTarget);
    try {
        return await apiRequest("../api/manage/actions.php", {
            method: "POST",
            body: JSON.stringify({
                action,
                ...payload,
            }),
        });
    } catch (error) {
        showMessage(error.message || "Die Aktion konnte nicht abgeschlossen werden.", "danger", messageTarget);
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

function showMessage(message, type, target = dom.messageArea) {
    clearMessageTimer(target);
    target.className = `alert alert-${type}`;
    target.textContent = message;
    target.classList.remove("d-none");
    if (target === dom.messageArea) {
        messageTimers.set(target, window.setTimeout(() => clearMessage(target), PAGE_ALERT_TIMEOUT_MS));
    }
}

function clearMessage(target = dom.messageArea) {
    clearMessageTimer(target);
    target.className = "d-none";
    target.textContent = "";
}

function clearMessageTimer(target) {
    const timer = messageTimers.get(target);
    if (timer) {
        window.clearTimeout(timer);
        messageTimers.delete(target);
    }
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
    beginRequest();
    try {
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
    } finally {
        endRequest();
    }
}

function beginRequest() {
    activeRequestCount++;
    updateSystemStatus();
}

function endRequest() {
    activeRequestCount = Math.max(0, activeRequestCount - 1);
    updateSystemStatus();
}

function updateSystemStatus() {
    const working = activeRequestCount > 0;
    dom.systemStatus.classList.toggle("is-working", working);
    dom.systemStatus.classList.toggle("is-ready", !working);
    dom.systemStatusText.textContent = working ? "Arbeitet" : "Bereit";
    dom.systemStatus.setAttribute("aria-label", working ? "System arbeitet" : "System bereit");
}
