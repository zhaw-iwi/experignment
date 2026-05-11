const state = {
    dashboard: null,
    selectedExperimentId: null,
};

const dom = {
    allowedCount: document.getElementById("allowedCount"),
    reloadButton: document.getElementById("reloadButton"),
    messageArea: document.getElementById("messageArea"),
    experimentList: document.getElementById("experimentList"),
    newExperimentButton: document.getElementById("newExperimentButton"),
    experimentForm: document.getElementById("experimentForm"),
    deleteExperimentButton: document.getElementById("deleteExperimentButton"),
    experimentId: document.getElementById("experimentId"),
    experimentName: document.getElementById("experimentName"),
    experimentDescription: document.getElementById("experimentDescription"),
    eligibilityMode: document.getElementById("eligibilityMode"),
    conditionMode: document.getElementById("conditionMode"),
    sortOrder: document.getElementById("sortOrder"),
    isOpen: document.getElementById("isOpen"),
    requiresTimeSlot: document.getElementById("requiresTimeSlot"),
    conditionList: document.getElementById("conditionList"),
    conditionForm: document.getElementById("conditionForm"),
    conditionId: document.getElementById("conditionId"),
    conditionName: document.getElementById("conditionName"),
    conditionSortOrder: document.getElementById("conditionSortOrder"),
    allowForm: document.getElementById("allowForm"),
    allowEmail: document.getElementById("allowEmail"),
    bulkAllowForm: document.getElementById("bulkAllowForm"),
    bulkAllowEmails: document.getElementById("bulkAllowEmails"),
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
    assignForm: document.getElementById("assignForm"),
    assignEmail: document.getElementById("assignEmail"),
    assignConditionId: document.getElementById("assignConditionId"),
    randomizeForm: document.getElementById("randomizeForm"),
    randomSeed: document.getElementById("randomSeed"),
    allocationFields: document.getElementById("allocationFields"),
    eligibilityList: document.getElementById("eligibilityList"),
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
    participationFilter: document.getElementById("participationFilter"),
    participationRows: document.getElementById("participationRows"),
};

document.addEventListener("DOMContentLoaded", () => {
    wireEvents();
    dom.randomSeed.value = defaultSeed();
    loadDashboard();
});

function wireEvents() {
    dom.reloadButton.addEventListener("click", loadDashboard);
    dom.newExperimentButton.addEventListener("click", () => {
        state.selectedExperimentId = null;
        clearExperimentForm();
        renderAll();
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
        await loadDashboard("Experiment gespeichert.");
    });

    dom.deleteExperimentButton.addEventListener("click", async () => {
        const experiment = selectedExperiment();
        if (!experiment) {
            showMessage("Bitte wählen Sie zuerst ein Experiment aus.", "warning");
            return;
        }
        if (!window.confirm(`Experiment "${experiment.name}" mit allen zugehörigen Daten löschen?`)) {
            return;
        }
        await postAction("delete_experiment", { experimentId: experiment.id });
        state.selectedExperimentId = null;
        await loadDashboard("Experiment gelöscht.");
    });

    dom.conditionForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const experiment = selectedExperiment();
        if (!experiment) {
            showMessage("Bitte wählen Sie zuerst ein Experiment aus.", "warning");
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
            showMessage("Bitte wählen Sie zuerst ein Experiment aus.", "warning");
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
            showMessage("Bitte wählen Sie zuerst ein Experiment aus.", "warning");
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
            showMessage("Bitte wählen Sie zuerst ein Experiment aus.", "warning");
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
            showMessage("Bitte wählen Sie zuerst ein Experiment aus.", "warning");
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
            showMessage("Bitte wählen Sie zuerst ein Experiment aus.", "warning");
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

    dom.participationFilter.addEventListener("change", () => {
        renderParticipations();
    });
}

async function loadDashboard(successMessage = "") {
    try {
        const dashboard = await apiRequest("../api/manage/dashboard.php", { method: "GET" });
        state.dashboard = dashboard;
        if (!state.selectedExperimentId && dashboard.experiments.length > 0) {
            state.selectedExperimentId = dashboard.experiments[0].id;
        }
        renderAll();
        if (successMessage) {
            showMessage(successMessage, "success");
        } else {
            clearMessage();
        }
    } catch (error) {
        showMessage(error.message || "Dashboard konnte nicht geladen werden.", "danger");
    }
}

function renderAll() {
    const dashboard = state.dashboard || { experiments: [], participations: [], allowedStudentCount: 0 };
    dom.allowedCount.textContent = `${dashboard.allowedStudentCount || 0} Studierende`;
    renderExperimentList();
    renderExperimentForm();
    renderConditionSection();
    renderFieldSection();
    renderPoolCounts();
    renderPoolRows();
    renderEligibilitySection();
    renderEligibilityList();
    renderSlotSection();
    renderSlotChoices();
    renderParticipationFilter();
    renderParticipations();
}

function renderExperimentList() {
    dom.experimentList.innerHTML = "";
    for (const experiment of state.dashboard.experiments || []) {
        const button = document.createElement("button");
        button.type = "button";
        button.className = `list-group-item list-group-item-action ${experiment.id === state.selectedExperimentId ? "active" : ""}`;
        button.innerHTML = `
            <div class="d-flex justify-content-between gap-2">
                <strong>${escapeHtml(experiment.name)}</strong>
                <span class="badge ${experiment.isOpen ? "text-bg-success" : "text-bg-secondary"}">${experiment.isOpen ? "offen" : "geschlossen"}</span>
            </div>
            <div class="small text-secondary">${experiment.counts.participations} Zuweisungen, ${experiment.counts.confirmed} angerechnet</div>
        `;
        button.addEventListener("click", () => {
            state.selectedExperimentId = experiment.id;
            clearConditionForm();
            clearFieldForm();
            clearSlotForm();
            renderAll();
        });
        dom.experimentList.appendChild(button);
    }
}

function renderExperimentForm() {
    const experiment = selectedExperiment();
    if (!experiment) {
        clearExperimentForm();
        dom.deleteExperimentButton.disabled = true;
        return;
    }

    dom.deleteExperimentButton.disabled = false;
    dom.experimentId.value = experiment.id;
    dom.experimentName.value = experiment.name || "";
    dom.experimentDescription.value = experiment.description || "";
    dom.eligibilityMode.value = experiment.eligibilityMode;
    dom.conditionMode.value = experiment.conditionMode;
    dom.sortOrder.value = experiment.sortOrder;
    dom.isOpen.checked = experiment.isOpen;
    dom.requiresTimeSlot.checked = experiment.requiresTimeSlot;
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

function renderParticipationFilter() {
    const current = dom.participationFilter.value;
    dom.participationFilter.innerHTML = `<option value="">Alle Experimente</option>`;
    for (const experiment of state.dashboard.experiments || []) {
        const option = document.createElement("option");
        option.value = experiment.id;
        option.textContent = experiment.name;
        dom.participationFilter.appendChild(option);
    }
    dom.participationFilter.value = current;
}

function renderParticipations() {
    const filter = valueOrNull(dom.participationFilter.value);
    dom.participationRows.innerHTML = "";
    const rows = (state.dashboard.participations || []).filter((participation) => {
        return filter === null || participation.experimentId === filter;
    });

    for (const participation of rows) {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${escapeHtml(participation.email)}</td>
            <td>${escapeHtml(participation.experimentName)}</td>
            <td>${escapeHtml(participation.conditionName || "-")}</td>
            <td>${escapeHtml(participation.slotLabel || "-")}</td>
            <td></td>
            <td>${participation.confirmed ? "Ja" : "Nein"}</td>
            <td class="text-end"></td>
        `;

        const appointmentCell = row.children[4];
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

        const actions = row.children[6];
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
        cell.colSpan = 7;
        cell.className = "text-secondary py-3";
        cell.textContent = "Keine Zuweisungen vorhanden.";
        row.appendChild(cell);
        dom.participationRows.appendChild(row);
    }
}

function selectedExperiment() {
    return (state.dashboard?.experiments || []).find((experiment) => experiment.id === state.selectedExperimentId) || null;
}

function clearExperimentForm() {
    dom.experimentId.value = "";
    dom.experimentName.value = "";
    dom.experimentDescription.value = "";
    dom.eligibilityMode.value = "selected";
    dom.conditionMode.value = "none";
    dom.sortOrder.value = "0";
    dom.isOpen.checked = false;
    dom.requiresTimeSlot.checked = false;
    dom.deleteExperimentButton.disabled = true;
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
