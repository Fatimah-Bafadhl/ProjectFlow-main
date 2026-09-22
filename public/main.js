/* ==========================================
   1. تهيئة الأحداث العامة وتفعيل القائمة الجانبية وتنبيهات الجلسة
========================================== */
document.addEventListener("DOMContentLoaded", () => {
    // تحديد الصفحة الحالية وتفعيل العنصر النشط في القائمة الجانبية تلقائياً[cite: 1]
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll(".navigation-sidebar .nav-link");

    navLinks.forEach(link => {
        link.classList.remove("active");
        const linkHref = link.getAttribute("href");
        if (linkHref && currentPath.includes(linkHref)) {
            link.classList.add("active");
        }
    });

    // عرض رسالة النجاح من الجلسة إن وجدت[cite: 1]
    if (window.sessionSuccessMessage) {
        const successModalEl = document.getElementById('successModal');
        const successTextEl = document.getElementById('successModalText');
        if (successModalEl) {
            if (successTextEl) {
                successTextEl.innerText = window.sessionSuccessMessage;
            }
            const successModal = new bootstrap.Modal(successModalEl);
            successModal.show();
        }
    }

    // ربط مستمعي الأحداث لحقول التواريخ[cite: 1]
    const startDateInput = document.getElementById('startDateInput');
    const endDateInput = document.getElementById('endDateInput');
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', validateDates);
        endDateInput.addEventListener('change', validateDates);
    }

       const projectStartDateInput = document.getElementById('projectStartDateInput');
    const projectEndDateInput = document.getElementById('projectEndDateInput');
    if (projectStartDateInput && projectEndDateInput) {
        projectStartDateInput.addEventListener('change', validateDates);
        projectEndDateInput.addEventListener('change', validateDates);
    }

    // Live search filter for assignment checkbox lists (Create Project page)
    const searchInputs = document.querySelectorAll('.assignment-search');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const targetId = this.getAttribute('data-target');
            const listContainer = document.getElementById(targetId);
            if (!listContainer) return;

            const query = this.value.trim().toLowerCase();
            const items = listContainer.querySelectorAll('.form-check');

            items.forEach(item => {
                const label = item.querySelector('.form-check-label');
                const text = label ? label.textContent.toLowerCase() : '';
                if (text.includes(query)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});

/* ==========================================
   2. إدارة عمليات المشاريع (Projects Operations)[cite: 1]
========================================== */
function prepareAddProjectModal(storeUrl) {
        const panelTitle = document.getElementById('projectPanelTitle');
    const projectForm = document.getElementById('projectForm');
    const methodInput = document.getElementById('projectFormMethod');

    if (panelTitle) panelTitle.innerText = "إضافة مشروع جديد";
    if (projectForm) {
        projectForm.reset();
        if (storeUrl) projectForm.action = storeUrl;
    }
    if (methodInput) methodInput.value = "POST";

    const companyInput = document.getElementById('projectCompanyNameInput');
    if (companyInput) companyInput.value = '';

        const panelEl = document.getElementById('projectPanel');
    if (panelEl) {
        let instance = bootstrap.Offcanvas.getInstance(panelEl);
        if (!instance) instance = new bootstrap.Offcanvas(panelEl);
        instance.show();
    }
}

function openEditProjectModal(button, updateUrl) {
    const projectCard = button.closest('.project-card-wrapper');
        const panelTitle = document.getElementById('projectPanelTitle');
    const projectForm = document.getElementById('projectForm');
    const methodInput = document.getElementById('projectFormMethod');

    if (panelTitle) panelTitle.innerText = "تعديل المشروع";
    if (projectForm && updateUrl) projectForm.action = updateUrl;
    if (methodInput) methodInput.value = "PUT";

    if (projectCard) {
        const nameInput = document.getElementById('projectNameInput');
        const companyInput = document.getElementById('projectCompanyNameInput');
        const descInput = document.getElementById('projectDescInput');
        const startDateInput = document.getElementById('projectStartDateInput');
        const endDateInput = document.getElementById('projectEndDateInput');
        const statusSelect = document.getElementById('projectStatusSelect');

        if (nameInput) nameInput.value = projectCard.getAttribute('data-project-name') || '';
        if (companyInput) companyInput.value = projectCard.getAttribute('data-company-name') || '';
        if (descInput) descInput.value = projectCard.getAttribute('data-project-desc') || '';
        if (startDateInput) startDateInput.value = projectCard.getAttribute('data-start-date') || '';
        if (endDateInput) endDateInput.value = projectCard.getAttribute('data-end-date') || '';
        if (statusSelect) statusSelect.value = projectCard.getAttribute('data-status') || 'قيد التنفيذ';
    }

        const panelEl = document.getElementById('projectPanel');
    if (panelEl) {
        let instance = bootstrap.Offcanvas.getInstance(panelEl);
        if (!instance) instance = new bootstrap.Offcanvas(panelEl);
        instance.show();
    }
}

function openDeleteProjectModal(button, deleteUrl) {
    const projectCard = button.closest('.project-card-wrapper');
    const projectName = projectCard ? projectCard.getAttribute('data-project-name') : '';
    
    const deleteModalText = document.getElementById('deleteProjectModalText');
    if (deleteModalText) deleteModalText.innerText = `هل تريد حذف مشروع ${projectName}؟`;

    const deleteForm = document.getElementById('deleteProjectForm');
    if (deleteForm && deleteUrl) deleteForm.action = deleteUrl;

    const modalEl = document.getElementById('deleteProjectModal');
    if (modalEl) {
        const deleteModal = new bootstrap.Modal(modalEl);
        deleteModal.show();
    }
}

/* dates (tasks&project) */

function validateDates() {
    const startDateInput = document.getElementById('startDateInput');
    const endDateInput = document.getElementById('endDateInput');
    
    if (startDateInput && endDateInput && startDateInput.value) {
        endDateInput.min = startDateInput.value;
        if (endDateInput.value && endDateInput.value < startDateInput.value) {
            endDateInput.value = startDateInput.value;
        }
    }

    const projectStartDateInput = document.getElementById('projectStartDateInput');
    const projectEndDateInput = document.getElementById('projectEndDateInput');
    
    if (projectStartDateInput && projectEndDateInput && projectStartDateInput.value) {
        projectEndDateInput.min = projectStartDateInput.value;
        if (projectEndDateInput.value && projectEndDateInput.value < projectStartDateInput.value) {
            projectEndDateInput.value = '';
            alert('تاريخ الانتهاء يجب أن يكون بعد أو مساوياً لتاريخ البدء.');
        }
    }
}

/* ==========================================
   Task Panel (Offcanvas) — Add / Edit + Attachments
========================================== */

// JS-side queue for files not yet uploaded (mirrored to the real input)
let taskAttachmentQueue = [];

function openTaskPanel() {
    const panelEl = document.getElementById('taskPanel');
    if (!panelEl) return;
    let instance = bootstrap.Offcanvas.getInstance(panelEl);
    if (!instance) instance = new bootstrap.Offcanvas(panelEl);
    instance.show();
}

function clearTaskAttachmentQueue() {
    taskAttachmentQueue = [];
    const input = document.getElementById('taskAttachmentsInput');
    if (input) input.value = '';
    renderTaskAttachmentsPreview();
}

function prepareAddModal(button) {
    const panelTitle = document.getElementById('taskPanelTitle');
    const taskForm = document.getElementById('taskForm');
    const methodInput = document.getElementById('taskFormMethod');
    const redirectInput = document.getElementById('taskRedirectInput');

    if (panelTitle) panelTitle.innerText = "إضافة مهمة";
    if (taskForm) {
        taskForm.reset();
        taskForm.action = "/tasks";
    }
    if (methodInput) methodInput.value = "POST";
    if (redirectInput) redirectInput.value = window.location.pathname + window.location.search;

    // Add-mode never shows existing attachments
    const existingBlock = document.getElementById('existingAttachmentsBlock');
    const existingList = document.getElementById('existingAttachmentsList');
    if (existingBlock) existingBlock.classList.add('d-none');
    if (existingList) existingList.innerHTML = '';

    // Reset new-files queue
    clearTaskAttachmentQueue();

    // Reset project (may have been disabled by a prior edit)
    const projectSelect = document.getElementById('projectIdInput');
    if (projectSelect) {
        projectSelect.removeAttribute('disabled');
        projectSelect.value = '';
    }

    // Reset date limits + stage dropdown
    const startDateInput = document.getElementById('startDateInput');
    const endDateInput = document.getElementById('endDateInput');
    if (startDateInput) { startDateInput.removeAttribute('min'); startDateInput.removeAttribute('max'); }
    if (endDateInput) { endDateInput.removeAttribute('min'); endDateInput.removeAttribute('max'); }

    const stageSelect = document.getElementById('stageIdInput');
    if (stageSelect) stageSelect.innerHTML = '<option value="">اختر مشروعاً أولاً</option>';

    // Optional project preselect (used by the add button on projects/show)
    const preselectId = button && button.dataset ? button.dataset.preselectProject : null;
    if (preselectId && projectSelect) {
        projectSelect.value = preselectId;
        updateProjectDatesLimits();
        updateStageOptions();
    }

    openTaskPanel();
}

function openEditModal(button) {
    const taskRow = button.closest('[data-task-id]');
    if (!taskRow) return;

    const taskId       = taskRow.getAttribute('data-task-id');
    const taskTitle    = taskRow.getAttribute('data-task-title') || '';
    const projectId    = taskRow.getAttribute('data-project-id') || '';
    const stageId      = taskRow.getAttribute('data-stage-id') || '';
    const assignedToRaw = taskRow.getAttribute('data-assigned-to') || '[]';
    const description  = taskRow.getAttribute('data-description') || '';
    const startDate    = taskRow.getAttribute('data-start-date') || '';
    const endDate      = taskRow.getAttribute('data-end-date') || '';
    const status       = taskRow.getAttribute('data-status') || '';
    const priority     = taskRow.getAttribute('data-priority') || 'متوسط';
    const attachmentsRaw = taskRow.getAttribute('data-attachments') || '[]';

        const panelTitle = document.getElementById('taskPanelTitle');
    const taskForm = document.getElementById('taskForm');
    const methodInput = document.getElementById('taskFormMethod');
    const redirectInput = document.getElementById('taskRedirectInput');

    if (panelTitle) panelTitle.innerText = "تعديل المهمة";
    if (taskForm) taskForm.action = `/tasks/${taskId}`;
    if (methodInput) methodInput.value = "PUT";
    if (redirectInput) redirectInput.value = window.location.pathname + window.location.search;

    // Text fields
    if (document.getElementById('taskNameInput')) document.getElementById('taskNameInput').value = taskTitle;
    if (document.getElementById('descriptionInput')) document.getElementById('descriptionInput').value = description;

    // Project: lock to the current one
    const projectSelect = document.getElementById('projectIdInput');
    if (projectSelect) {
        projectSelect.value = projectId;
        projectSelect.setAttribute('disabled', 'disabled');
    }
    // Order matters: dates + stages depend on project selection above
    updateProjectDatesLimits();
    updateStageOptions();

    const stageSelect = document.getElementById('stageIdInput');
    if (stageSelect) stageSelect.value = stageId;

               let assignedToIds = [];
    try { assignedToIds = JSON.parse(assignedToRaw) || []; } catch (e) { assignedToIds = []; }
    assignedToIds = assignedToIds.map(String);
    document.querySelectorAll('.task-assignee-checkbox').forEach(cb => {
        cb.checked = assignedToIds.includes(cb.value);
    });
    if (document.getElementById('startDateInput')) document.getElementById('startDateInput').value = startDate ? startDate.split('T')[0] : '';
    if (document.getElementById('endDateInput')) document.getElementById('endDateInput').value = endDate ? endDate.split('T')[0] : '';
    if (document.getElementById('statusSelect')) document.getElementById('statusSelect').value = status;
    if (document.getElementById('prioritySelect')) document.getElementById('prioritySelect').value = priority;

    // Reset new-files queue for this edit session
    clearTaskAttachmentQueue();

    // Populate existing-attachments block from data-attachments JSON
    let attachments = [];
    try { attachments = JSON.parse(attachmentsRaw) || []; } catch (e) { attachments = []; }
    renderExistingTaskAttachments(attachments);

    openTaskPanel();
}

function renderExistingTaskAttachments(attachments) {
    const block = document.getElementById('existingAttachmentsBlock');
    const list = document.getElementById('existingAttachmentsList');
    if (!block || !list) return;

    list.innerHTML = '';

    if (!attachments || attachments.length === 0) {
        block.classList.add('d-none');
        return;
    }

    attachments.forEach(function (att) {
        const row = document.createElement('div');
        row.className = 'task-existing-attachment';

        const deleteUrl = `/attachments/${att.id}`;
        const safeTitle = escapeHtml(att.title || '');
        const safeUrl = String(att.url || '#').replace(/"/g, '&quot;');

        row.innerHTML =
            '<div class="file-info">' +
                '<i class="fa-solid fa-paperclip file-icon"></i>' +
                '<a class="file-name" href="' + safeUrl + '" target="_blank" rel="noopener">' + safeTitle + '</a>' +
            '</div>' +
            '<button type="button" class="delete-btn" title="حذف المرفق"><i class="fa-regular fa-trash-can"></i></button>';

        row.querySelector('.delete-btn').addEventListener('click', function () {
            openDeleteTaskAttachment(deleteUrl, att.title || '');
        });

        list.appendChild(row);
    });

    block.classList.remove('d-none');
}

function showTaskAttachmentsPreview(input) {
    if (!input.files) return;

    // Merge newly selected files into the queue (dedupe by name+size)
    Array.from(input.files).forEach(function (file) {
        const exists = taskAttachmentQueue.some(function (f) {
            return f.name === file.name && f.size === file.size;
        });
        if (!exists) taskAttachmentQueue.push(file);
    });

    // Clear the input's own FileList; we own the queue from here.
    input.value = '';
    syncTaskAttachmentQueueToInput();
    renderTaskAttachmentsPreview();
}

function renderTaskAttachmentsPreview() {
    const preview = document.getElementById('taskAttachmentsPreview');
    if (!preview) return;
    preview.innerHTML = '';

    taskAttachmentQueue.forEach(function (file, index) {
        const chip = document.createElement('div');
        chip.className = 'task-attachment-chip';
        const safeName = escapeHtml(file.name);
        chip.innerHTML =
            '<i class="fa-regular fa-file chip-icon"></i>' +
            '<span class="chip-name">' + safeName + '</span>' +
            '<button type="button" class="chip-remove" title="إزالة"><i class="fa-solid fa-xmark"></i></button>';

        chip.querySelector('.chip-remove').addEventListener('click', function () {
            taskAttachmentQueue.splice(index, 1);
            syncTaskAttachmentQueueToInput();
            renderTaskAttachmentsPreview();
        });

        preview.appendChild(chip);
    });
}

function syncTaskAttachmentQueueToInput() {
    const input = document.getElementById('taskAttachmentsInput');
    if (!input) return;
    try {
        const dt = new DataTransfer();
        taskAttachmentQueue.forEach(function (file) { dt.items.add(file); });
        input.files = dt.files;
    } catch (e) {
        // DataTransfer unsupported (very old browsers): the queue still
        // works for display, but removing items won't reflect in the form.
        console.warn('DataTransfer not supported; attachment removal limited.', e);
    }
}

function openDeleteTaskAttachment(deleteUrl, title) {
    const form = document.getElementById('deleteTaskAttachmentForm');
    const textEl = document.getElementById('deleteTaskAttachmentText');
    if (form) form.action = deleteUrl;
    if (textEl) textEl.innerText = 'هل تريد حذف المرفق "' + title + '"؟';

    const modalEl = document.getElementById('deleteTaskAttachmentModal');
    if (modalEl) {
        let instance = bootstrap.Modal.getInstance(modalEl);
        if (!instance) instance = new bootstrap.Modal(modalEl);
        instance.show();
    }
}

function submitDeleteTaskAttachment() {
    const form = document.getElementById('deleteTaskAttachmentForm');
    if (form) form.submit();
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
}
function openDeleteModal(button) {
    const taskCard = button.closest('[data-task-id]');
    if (!taskCard) return;

    const taskId = taskCard.getAttribute('data-task-id');
    const taskTitle = taskCard.getAttribute('data-task-title') || taskCard.querySelector('.task-name')?.innerText || '';
    
    const deleteModalText = document.getElementById('deleteModalText');
    const deleteTaskForm = document.getElementById('deleteTaskForm');

    if (deleteModalText) deleteModalText.innerText = `هل تريد حذف مهمة "${taskTitle.trim()}" ؟`;
    if (deleteTaskForm) deleteTaskForm.action = `/tasks/${taskId}`;

    const modalEl = document.getElementById('deleteModal');
    if (modalEl) {
        let modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (!modalInstance) modalInstance = new bootstrap.Modal(modalEl);
        modalInstance.show();
    }
}

function updateProjectDatesLimits() {
    const projectSelect = document.getElementById('projectIdInput');
    if (!projectSelect) return;
    
    const selectedOption = projectSelect.options[projectSelect.selectedIndex];
    const startDateInput = document.getElementById('startDateInput');
    const endDateInput = document.getElementById('endDateInput');

    if (!startDateInput || !endDateInput) return;

    if (selectedOption && selectedOption.value) {
        const projectStart = selectedOption.getAttribute('data-start');
        const projectEnd = selectedOption.getAttribute('data-end');

        if (projectStart) startDateInput.min = projectStart;
        if (projectEnd) startDateInput.max = projectEnd;
        if (projectStart) endDateInput.min = projectStart;
        if (projectEnd) endDateInput.max = projectEnd;
    }
}

function updateStageOptions() {
    const projectSelect = document.getElementById('projectIdInput');
    const stageSelect = document.getElementById('stageIdInput');
    if (!projectSelect || !stageSelect) return;

    const selectedOption = projectSelect.options[projectSelect.selectedIndex];
    stageSelect.innerHTML = '';

    if (selectedOption && selectedOption.value) {
        let stages = [];
        try {
            stages = JSON.parse(selectedOption.getAttribute('data-stages') || '[]');
        } catch (e) {
            stages = [];
        }

        const emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = stages.length ? 'بدون مرحلة' : 'لا توجد مراحل لهذا المشروع';
        stageSelect.appendChild(emptyOption);

        stages.forEach(stage => {
            const opt = document.createElement('option');
            opt.value = stage.id;
            opt.textContent = stage.label;
            stageSelect.appendChild(opt);
        });
    } else {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'اختر مشروعاً أولاً';
        stageSelect.appendChild(opt);
    }
}

/* tasks page*/
function prepareAddClientModal(storeUrl) {
    const panelTitle = document.getElementById('clientPanelTitle');
    const clientForm = document.getElementById('clientForm');
    const methodInput = document.getElementById('clientFormMethod');
    const nameInput = document.getElementById('clientNameInput');
    const roleInput = document.getElementById('clientRoleInput');
    const passwordGroup = document.getElementById('clientPasswordGroup');
    const passwordInput = document.getElementById('clientPasswordInput');

    if (panelTitle) panelTitle.innerText = "إضافة عميل جديد";
    if (clientForm) {
        clientForm.reset();
        if (storeUrl) clientForm.action = storeUrl;
    }
    if (methodInput) methodInput.value = "POST";

    // Add mode -> users.store expects 'username', not 'name'
    if (nameInput) nameInput.setAttribute('name', 'username');

    // Add mode -> submit role=client (enabled hidden input)
    if (roleInput) roleInput.removeAttribute('disabled');

    // Add mode -> reveal + enable password
    if (passwordGroup) passwordGroup.classList.remove('d-none');
    if (passwordInput) {
        passwordInput.removeAttribute('disabled');
        passwordInput.setAttribute('required', 'required');
    }

    const checkboxContainer = document.getElementById('clientProjectsCheckboxes');
    if (checkboxContainer) {
        checkboxContainer.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
    }

    const panelEl = document.getElementById('clientPanel');
    if (panelEl) {
        let instance = bootstrap.Offcanvas.getInstance(panelEl);
        if (!instance) instance = new bootstrap.Offcanvas(panelEl);
        instance.show();
    }
}

function openEditClientModal(button, updateUrl) {
    const clientCard = button.closest('[data-client-id]');
    const panelTitle = document.getElementById('clientPanelTitle');
    const clientForm = document.getElementById('clientForm');
    const methodInput = document.getElementById('clientFormMethod');
    const nameInput = document.getElementById('clientNameInput');
    const roleInput = document.getElementById('clientRoleInput');
    const passwordGroup = document.getElementById('clientPasswordGroup');
    const passwordInput = document.getElementById('clientPasswordInput');

    if (panelTitle) panelTitle.innerText = "تعديل بيانات العميل";
    if (clientForm && updateUrl) clientForm.action = updateUrl;
    if (methodInput) methodInput.value = "PUT";

    // Edit mode -> clients.update expects 'name', not 'username'
    if (nameInput) nameInput.setAttribute('name', 'name');

    // Edit mode -> drop role entirely
    if (roleInput) roleInput.setAttribute('disabled', 'disabled');

    // Edit mode -> hide + disable password so it isn't sent empty
    if (passwordGroup) passwordGroup.classList.add('d-none');
    if (passwordInput) {
        passwordInput.setAttribute('disabled', 'disabled');
        passwordInput.removeAttribute('required');
        passwordInput.value = '';
    }

        if (clientCard) {
        if (document.getElementById('clientNameInput')) document.getElementById('clientNameInput').value = clientCard.getAttribute('data-client-name') || '';
        if (document.getElementById('companyNameInput')) document.getElementById('companyNameInput').value = clientCard.getAttribute('data-company-name') || '';
        if (document.getElementById('clientEmailInput')) document.getElementById('clientEmailInput').value = clientCard.getAttribute('data-client-email') || '';
        if (document.getElementById('clientPhoneInput')) document.getElementById('clientPhoneInput').value = clientCard.getAttribute('data-client-phone') || '';

        const projectIdsAttr = clientCard.getAttribute('data-client-project-ids') || '';
        const projectIds = projectIdsAttr.split(',').filter(id => id !== '');
        const checkboxContainer = document.getElementById('clientProjectsCheckboxes');
        if (checkboxContainer) {
            checkboxContainer.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.checked = projectIds.includes(cb.value);
            });
        }
    }

        const panelEl = document.getElementById('clientPanel');
    if (panelEl) {
        let instance = bootstrap.Offcanvas.getInstance(panelEl);
        if (!instance) instance = new bootstrap.Offcanvas(panelEl);
        instance.show();
    }
}

function openDeleteClientModal(button, deleteUrl) {
    const clientCard = button.closest('[data-client-id]');
    const clientName = clientCard ? clientCard.getAttribute('data-client-name') : 'العميل';
    
    const deleteModalText = document.getElementById('deleteClientModalText');
    if (deleteModalText) deleteModalText.innerText = `هل تريد بالتأكيد حذف العميل "${clientName}"؟`;

    const deleteForm = document.getElementById('deleteClientForm');
    if (deleteForm && deleteUrl) {
        deleteForm.action = deleteUrl;
    }

    const modalEl = document.getElementById('deleteClientModal');
    if (modalEl) {
        const deleteModal = new bootstrap.Modal(modalEl);
        deleteModal.show();
    }
}

/* Users page — prepare Add User Offcanvas */
function prepareAddUserPanel() {
    const form = document.getElementById('addUserForm');
    if (form) form.reset();

    // Reset the role dropdown to the first option
    const roleSelect = document.getElementById('addUserRole');
    if (roleSelect) roleSelect.selectedIndex = 0;

    // Trigger the toggle function to hide/show correct fields
    if (typeof toggleAddUserFields === 'function') {
        toggleAddUserFields();
    } else {
        // Fallback if the inline function isn't accessible
        const deptField = document.getElementById('addEmployeeDeptField');
        const projectField = document.getElementById('addClientProjectField');
        if (deptField) deptField.classList.add('d-none');
        if (projectField) projectField.classList.add('d-none');
    }
}

/* Users page — open shared Edit User offcanvas, populated from the button's data-* attrs */
function openEditUserPanel(button) {
    const form = document.getElementById('editUserForm');
    if (form) form.action = button.getAttribute('data-update-url') || '';

    const usernameInput = document.getElementById('editUsernameInput');
    const emailInput    = document.getElementById('editEmailInput');
    const passInput     = document.getElementById('editPasswordInput');
    const phoneInput    = document.getElementById('editPhoneInput');
    const companyInput  = document.getElementById('editCompanyInput');
    const roleSelect    = document.getElementById('editRoleSelect');
    const deptInput     = document.getElementById('editDepartmentInput');

    if (usernameInput) usernameInput.value = button.getAttribute('data-username') || '';
    if (emailInput)    emailInput.value    = button.getAttribute('data-email') || '';
    if (passInput)     passInput.value     = '';
    if (phoneInput)    phoneInput.value    = button.getAttribute('data-phone') || '';
    if (companyInput)  companyInput.value  = button.getAttribute('data-company-name') || '';

    const role = button.getAttribute('data-role') || '';
    if (roleSelect) roleSelect.value = role;

    if (deptInput) deptInput.value = button.getAttribute('data-department') || '';

    // Projects checkboxes
    const projectIds = (button.getAttribute('data-project-ids') || '').split(',').filter(Boolean);
    document.querySelectorAll('#editClientProjectsList input[type="checkbox"]').forEach(cb => {
        cb.checked = projectIds.includes(cb.value);
    });

    // Show / hide role-conditional fields AFTER values are in
    toggleEditUserFields(role);

    const panelEl = document.getElementById('editUserOffcanvas');
    if (panelEl) {
        let instance = bootstrap.Offcanvas.getInstance(panelEl);
        if (!instance) instance = new bootstrap.Offcanvas(panelEl);
        instance.show();
    }
}

/* Toggle role-conditional fields inside the Edit User offcanvas */
function toggleEditUserFields(role) {
    const deptField = document.getElementById('editEmployeeDeptField');
    const deptInput = document.getElementById('editDepartmentInput');
    if (deptField && deptInput) {
        const isEmployee = role === 'employee';
        deptField.classList.toggle('d-none', !isEmployee);
        deptInput.required = isEmployee;
    }

    const projectField = document.getElementById('editClientProjectField');
    if (projectField) {
        const isClient = role === 'client';
        projectField.classList.toggle('d-none', !isClient);
        if (!isClient) {
            projectField.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        }
    }
}

/* Users page — delete confirmation (shared modal) */
function openDeleteUserModal(deleteUrl, username) {
    const textEl = document.getElementById('deleteUserModalText');
    if (textEl) textEl.innerText = `هل تريد بالتأكيد حذف المستخدم "${username}"؟`;

    const form = document.getElementById('deleteUserForm');
    if (form && deleteUrl) form.action = deleteUrl;

    const modalEl = document.getElementById('deleteUserModal');
    if (modalEl) {
        let instance = bootstrap.Modal.getInstance(modalEl);
        if (!instance) instance = new bootstrap.Modal(modalEl);
        instance.show();
    }
}

/* ==========================================
   5. صفحة تسجيل الدخول واستعادة كلمة المرور[cite: 1]
========================================== */
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('usernameInput');
    const passwordInput = document.getElementById('passwordInput');
    const rememberMeCheckbox = document.getElementById('rememberMe');
    const usernameError = document.getElementById('usernameError');
    const passwordError = document.getElementById('passwordError');

    if (usernameInput && localStorage.getItem('savedUsername')) {
        usernameInput.value = localStorage.getItem('savedUsername');
        if (rememberMeCheckbox) rememberMeCheckbox.checked = true;
    }

    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            resetErrors();

            const username = usernameInput ? usernameInput.value.trim() : '';
            const password = passwordInput ? passwordInput.value.trim() : '';
            
            const fvsPattern = /^[a-zA-Z0-9._%+-]+@fvs\.com\.sa$/;
            const generalEmailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!username) {
                e.preventDefault();
                showError(usernameInput, usernameError, 'يرجى إدخال البريد الإلكتروني');
                return;
            }

            if (!fvsPattern.test(username) && !generalEmailPattern.test(username)) {
                e.preventDefault();
                showError(usernameInput, usernameError, 'يرجى إدخال بريد إلكتروني صحيح');
                return;
            }

            if (!password) {
                e.preventDefault();
                showError(passwordInput, passwordError, 'يرجى إدخال كلمة المرور');
                return;
            }

            if (rememberMeCheckbox && rememberMeCheckbox.checked) {
                localStorage.setItem('savedUsername', username);
            } else {
                localStorage.removeItem('savedUsername');
            }
        });
    }

    function showError(inputEl, errorEl, message) {
        if (inputEl) inputEl.classList.add('is-invalid');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('d-none');
        }
    }

    function resetErrors() {
        if (usernameInput) usernameInput.classList.remove('is-invalid');
        if (passwordInput) passwordInput.classList.remove('is-invalid');
        if (usernameError) {
            usernameError.textContent = '';
            usernameError.classList.add('d-none');
        }
        if (passwordError) {
            passwordError.textContent = '';
            passwordError.classList.add('d-none');
        }
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const resetForm = document.getElementById('resetForm');
    const emailInput = document.getElementById('emailInput');
    const emailError = document.getElementById('emailError');
    const formSection = document.getElementById('formSection');
    const successState = document.getElementById('successState');

    if (resetForm) {
        resetForm.addEventListener('submit', (e) => {
            e.preventDefault();

            if (emailInput) emailInput.classList.remove('is-invalid');
            if (emailError) {
                emailError.textContent = '';
                emailError.classList.add('d-none');
            }

            const email = emailInput ? emailInput.value.trim() : '';
            const emailPattern = /^[a-zA-Z0-9._%+-]+@fvs\.com\.sa$/i;

            if (!email) {
                showErrorMsg('يرجى إدخال البريد الإلكتروني');
                return;
            }

            if (!emailPattern.test(email)) {
                showErrorMsg('الصيغة غير صحيحة، يجب أن ينتهي البريد بـ name@fvs.com.sa');
                return;
            }

            if (formSection) formSection.classList.add('d-none');
            if (successState) successState.classList.remove('d-none');
        });
    }

    function showErrorMsg(msg) {
        if (emailInput) emailInput.classList.add('is-invalid');
        if (emailError) {
            emailError.textContent = msg;
            emailError.classList.remove('d-none');
        }
    }
});

/* ==========================================
   6. إدارة الملف الشخصي (Profile Management)[cite: 1]
========================================== */
function handleProfileSubmit(event) {
    event.preventDefault();
    
    const phoneInput = document.getElementById('profilePhoneInput');
    const phoneRegex = /^05[0-9]{8}$/;

    if (phoneInput && phoneInput.value.trim() !== "") {
        if (!phoneRegex.test(phoneInput.value)) {
            phoneInput.setCustomValidity("يجب أن يبدأ رقم الجوال بـ 05 ويتكون من 10 أرقام");
            phoneInput.reportValidity();
            return;
        } else {
            phoneInput.setCustomValidity("");
        }
    }
    
    const nameInput = document.getElementById('profileNameInput');
    const emailInput = document.getElementById('profileEmailInput');

    const newName = nameInput ? nameInput.value : '';
    const newEmail = emailInput ? emailInput.value : '';
    
    const nameDisplay = document.getElementById('profileCardNameDisplay');
    const emailDisplay = document.getElementById('profileCardEmailDisplay');

    if (nameDisplay) nameDisplay.innerText = newName;
    if (emailDisplay) emailDisplay.innerText = newEmail;
    
    const nameParts = newName.trim().split(' ');
    let initials = nameParts[0] ? nameParts[0][0] : '';
    if (nameParts.length > 1) {
        initials += nameParts[nameParts.length - 1][0];
    }
    const avatarEl = document.getElementById('profileCardAvatar');
    if (avatarEl) avatarEl.innerText = initials;
    
    showStatusMessage("تم حفظ التعديلات بنجاح");
}

const phoneField = document.getElementById('profilePhoneInput');
if (phoneField) {
    phoneField.addEventListener('input', function() {
        this.setCustomValidity('');
    });
}


/* ==========================================
   7. الإعدادات، اللغة والترجمة، والإشعارات[cite: 1]
========================================== */
const translations = {
    ar: {
        settingsPageHeader: "الاعدادات",
        settingsPageSub: "إدارة تفضيلاتك وحسابك الشخصي",
        notificationsHeading: "الاشعارات",
        notificationsSub: "إشعارات البريد الإلكتروني",
        toggleEmailNotif: "تفعيل إشعارات البريد",
        languagesHeading: "اللغات",
        languagesSub: "لغة الواجهة",
        currentLangText: "العربية",
        changePassHeading: "تغيير كلمة المرور",
        changePassSub: "تحديث بيانات تسجيل الدخول الخاصة بك",
        currentPassLabel: "كلمة المرور الحالية",
        newPassLabel: "كلمة المرور الجديدة",
        confirmPassLabel: "تأكيد كلمة المرور الجديدة",
        updatePassBtn: "تحديث كلمة المرور"
    },
    en: {
        settingsPageHeader: "Settings",
        settingsPageSub: "Manage your preferences and personal account",
        notificationsHeading: "Notifications",
        notificationsSub: "Email notifications",
        toggleEmailNotif: "Enable Email Notifications",
        languagesHeading: "Languages",
        languagesSub: "Interface language",
        currentLangText: "English",
        changePassHeading: "Change Password",
        changePassSub: "Update your login credentials",
        currentPassLabel: "Current Password",
        newPassLabel: "New Password",
        confirmPassLabel: "Confirm New Password",
        updatePassBtn: "Update Password"
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const emailNotifToggle = document.getElementById('emailNotifToggle');
    if (emailNotifToggle && window.settingsRoutes && window.settingsRoutes.notifications) {
        emailNotifToggle.addEventListener('change', function() {
            const isChecked = this.checked;

            fetch(window.settingsRoutes.notifications, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ email_notifications: isChecked })
            })
            .then(async response => {
                const data = await response.json();
                if (response.ok && data.success) {
                    showStatusMessage(data.message || "تم تحديث إعدادات الإشعارات بنجاح");
                } else {
                    alert(data.message || 'حدث خطأ أثناء تحديث الإشعارات.');
                    emailNotifToggle.checked = !isChecked;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال بالخادم.');
                emailNotifToggle.checked = !isChecked;
            });
        });
    }

    const passwordChangeForm = document.getElementById('passwordChangeForm');
    const confirmPass = document.getElementById('confirmPassInput');

    if (confirmPass) {
        confirmPass.addEventListener('input', () => {
            confirmPass.setCustomValidity('');
        });
    }

    if (passwordChangeForm && window.settingsRoutes && window.settingsRoutes.passwordUpdate) {
        passwordChangeForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const currentPass = document.getElementById('currentPassInput').value.trim();
            const newPass = document.getElementById('newPassInput').value.trim();
            const confirmPassInput = document.getElementById('confirmPassInput');
            const confirmPassValue = confirmPassInput.value.trim();

            if (newPass !== confirmPassValue) {
                const currentLang = localStorage.getItem('preferredLang') || 'ar';
                const errorMsg = currentLang === 'en' 
                    ? 'Passwords do not match.' 
                    : 'كلمتا المرور غير متطابقتين.';

                confirmPassInput.setCustomValidity(errorMsg);
                confirmPassInput.reportValidity();
                return;
            }

            fetch(window.settingsRoutes.passwordUpdate, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({
                    current_password: currentPass,
                    new_password: newPass,
                    new_password_confirmation: confirmPassValue
                })
            })
            .then(async response => {
                const data = await response.json();
                if (response.ok && data.success) {
                    showStatusMessage(data.message || "تم تحديث كلمة المرور بنجاح");
                    passwordChangeForm.reset();
                } else {
                    alert(data.message || 'حدث خطأ أثناء تحديث كلمة المرور.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال بالخادم.');
            });
        });
    }

    const savedLang = localStorage.getItem('preferredLang') || 'ar';
    changeLanguage(savedLang);
});

function changeLanguage(lang) {
    localStorage.setItem('preferredLang', lang);

    const htmlRoot = document.documentElement;
    const bootstrapCSS = document.getElementById('bootstrapCSS');
    const langBadge = document.getElementById('currentLangBadge');

    if (htmlRoot) {
        if (lang === 'en') {
            htmlRoot.setAttribute('lang', 'en');
            htmlRoot.setAttribute('dir', 'ltr');
            if (bootstrapCSS) bootstrapCSS.href = "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css";
            if (langBadge) langBadge.textContent = translations.en.currentLangText;
        } else {
            htmlRoot.setAttribute('lang', 'ar');
            htmlRoot.setAttribute('dir', 'rtl');
            if (bootstrapCSS) bootstrapCSS.href = "https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css";
            if (langBadge) langBadge.textContent = translations.ar.currentLangText;
        }
    }

    const elementsToTranslate = document.querySelectorAll('[data-i18n]');
    elementsToTranslate.forEach(element => {
        const key = element.getAttribute('data-i18n');
        if (translations[lang] && translations[lang][key]) {
            element.textContent = translations[lang][key];
        }
    });

    updateActiveLanguageButtons(lang);
}

function updateActiveLanguageButtons(lang) {
    const arBtn = document.getElementById('langArBtn');
    const enBtn = document.getElementById('langEnBtn');

    if (arBtn && enBtn) {
        if (lang === 'ar') {
            arBtn.classList.add('bg-light', 'border-secondary');
            enBtn.classList.remove('bg-light', 'border-secondary');
        } else {
            enBtn.classList.add('bg-light', 'border-secondary');
            arBtn.classList.remove('bg-light', 'border-secondary');
        }
    }
}

/* ==========================================
   8. الدوال الموحدة (رسائل الحالة والإشعارات)[cite: 1]
========================================== */
function showStatusMessage(message) {
    const messageElement = document.getElementById('statusModalMessage');
    if (messageElement) {
        messageElement.innerText = message;
    }
    
    const modalEl = document.getElementById('statusMessageModal');
    if (modalEl) {
        const existingModal = bootstrap.Modal.getInstance(modalEl);
        if (existingModal) existingModal.hide();
        
        const statusModal = new bootstrap.Modal(modalEl);
        statusModal.show();
    }
}

function markNotificationsAsRead() {
    let url = window.notificationsReadUrl || '/notifications/read';

    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const badge = document.getElementById('notification-badge');
            const unreadText = document.getElementById('unread-text-count');
            
            if (badge) badge.remove();
            if (unreadText) unreadText.innerText = ' 0 جديد ';
        }
    })
    .catch(error => console.error('Error:', error));
}

/* ==========================================
   9. قسم التعليقات (Comments Section)[cite: 1]
========================================== */
let attachedFiles = [];
const MAX_ATTACHMENTS = 3; 

function prepareAddComment(formId) {
    const commentForm = document.getElementById(formId);
    if (commentForm) {
        commentForm.reset();
        attachedFiles = [];
        updateAttachmentsPreview();
    }
}

// دالة موحدة ومتكاملة لتعديل التعليق (تدمج النسخ المتعددة لـ openEditCommentModal)[cite: 1]
function openEditCommentModal(button, url) {
    const commentItem = button.closest('.comment-item');
    if (!commentItem) return;

    const commentText = commentItem.getAttribute('data-comment-text') || commentItem.querySelector('.comment-content')?.innerText || '';
    const commentAttachment = commentItem.getAttribute('data-attachment') || '';
    
    const editInput = document.getElementById('editCommentInput');
    const editForm = document.getElementById('editCommentForm');
    const editAttachmentInput = document.getElementById('editAttachmentInput');
    const removeAttachmentFlag = document.getElementById('removeAttachmentFlag');

    if (editInput) editInput.value = commentText.trim();
    if (editForm && url) editForm.action = url;
    if (editAttachmentInput) editAttachmentInput.value = '';
    if (removeAttachmentFlag) removeAttachmentFlag.value = '0';

    const editFileBox = document.getElementById('editFilePreviewBox');
    const editImageBox = document.getElementById('editImagePreviewBox');
    const editFileNameText = document.getElementById('editFileNameText');
    const editImageThumbnail = document.getElementById('editImageThumbnail');
    const noAttachmentAddBox = document.getElementById('noAttachmentAddBox');
    const removeAttachmentActionBox = document.getElementById('removeAttachmentActionBox');
    const editAttachmentLabel = document.getElementById('editAttachmentLabel');
    const addOrChangeBtnText = document.getElementById('addOrChangeBtnText');

    if (editFileBox && editImageBox && noAttachmentAddBox && removeAttachmentActionBox && editAttachmentLabel) {
        if (commentAttachment && commentAttachment !== '' && commentAttachment !== 'null') {
            const fileName = commentAttachment.split('/').pop();
            const extension = fileName.split('.').pop().toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(extension);
            const fullPath = window.assetStorageBaseUrl ? `${window.assetStorageBaseUrl}/${commentAttachment}` : `/storage/${commentAttachment}`;

            editAttachmentLabel.textContent = "المرفق الحالي:";
            if (isImage && editImageThumbnail) {
                editImageThumbnail.src = fullPath;
                editImageBox.classList.remove('d-none');
                editFileBox.classList.add('d-none');
            } else {
                if (editFileNameText) editFileNameText.textContent = fileName;
                editFileBox.classList.remove('d-none');
                editImageBox.classList.add('d-none');
            }
            noAttachmentAddBox.classList.add('d-none');
            removeAttachmentActionBox.classList.remove('d-none');
        } else {
            editAttachmentLabel.textContent = "إرفاق ملف أو صورة (اختياري):";
            editFileBox.classList.add('d-none');
            editImageBox.classList.add('d-none');
            noAttachmentAddBox.classList.remove('d-none');
            removeAttachmentActionBox.classList.add('d-none');
            if (addOrChangeBtnText) addOrChangeBtnText.textContent = "إضافة ملف أو صورة مع التعديل";
        }
    }

    const modalEl = document.getElementById('editCommentModal');
    if (modalEl) {
        let modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

// دالة موحدة لحذف التعليق (تم دمج التكرارات)[cite: 1]
function openDeleteCommentModal(button, url) {
    const deleteForm = document.getElementById('deleteCommentForm');
    if (deleteForm && url) {
        deleteForm.action = url;
    }
    const modalEl = document.getElementById('deleteCommentModal');
    if (modalEl) {
        let modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function handleFileSelect(event) {
    const files = Array.from(event.target.files);
    
    if (attachedFiles.length + files.length > MAX_ATTACHMENTS) {
        const textInput = document.getElementById('commentTextInput');
        if (textInput) {
            textInput.setCustomValidity(`تنبيه: الحد الأقصى المسموح به للإرفاق هو ${MAX_ATTACHMENTS} عناصر فقط (صور أو ملفات).`);
            textInput.reportValidity();
            textInput.oninput = function() { this.setCustomValidity(''); };
        }
        event.target.value = '';
        return;
    }

    files.forEach(file => {
        attachedFiles.push({ type: 'file', file: file });
    });
    renderAttachmentsPreview();
    event.target.value = '';
}

function handleImageSelect(event) {
    const files = Array.from(event.target.files);
    
    if (attachedFiles.length + files.length > MAX_ATTACHMENTS) {
        const textInput = document.getElementById('commentTextInput');
        if (textInput) {
            textInput.setCustomValidity(`تنبيه: الحد الأقصى المسموح به للإرفاق هو ${MAX_ATTACHMENTS} عناصر فقط (صور أو ملفات).`);
            textInput.reportValidity();
            textInput.oninput = function() { this.setCustomValidity(''); };
        }
        event.target.value = '';
        return;
    }

    files.forEach(file => {
        attachedFiles.push({ type: 'image', file: file });
    });
    renderAttachmentsPreview();
    event.target.value = '';
}

function removeAttachment(index) {
    attachedFiles.splice(index, 1);
    updateAttachmentsPreview();
}

function updateAttachmentsPreview() {
    const previewContainer = document.getElementById('attachmentsPreview');
    if (!previewContainer) return;

    previewContainer.innerHTML = '';
    attachedFiles.forEach((file, index) => {
        const fileBadge = document.createElement('div');
        fileBadge.className = 'badge bg-secondary d-flex align-items-center gap-2 p-2';
        fileBadge.innerHTML = `
            <span>${file.name}</span>
            <button type="button" class="btn-close btn-close-white btn-sm" onclick="removeAttachment(${index})"></button>
        `;
        previewContainer.appendChild(fileBadge);
    });
}

function renderAttachmentsPreview() {
    const previewContainer = document.getElementById('attachmentsPreview');
    if (!previewContainer) return;

    previewContainer.innerHTML = '';
    attachedFiles.forEach((item, index) => {
        const badge = document.createElement('div');
        badge.className = 'badge bg-light text-dark border d-flex align-items-center gap-2 p-2 rounded-3';
        const icon = item.type === 'image' ? 'fa-regular fa-image' : 'fa-solid fa-paperclip';
        badge.innerHTML = `
            <i class="${icon}" style="color: #8A84AD;"></i>
            <span class="small">${item.file.name}</span>
            <i class="fa-solid fa-xmark text-danger cursor-pointer ms-1" onclick="removeAttachment(${index})"></i>
        `;
        previewContainer.appendChild(badge);
    });
}

function handleCommentSubmit(event) {
    const textInput = document.getElementById('commentTextInput');
    const commentText = textInput ? textInput.value.trim() : '';

    if (!commentText && attachedFiles.length === 0) {
        event.preventDefault();
        if (textInput) {
            textInput.setCustomValidity("يرجى كتابة تعليق أو إدراج ملفات/صور قبل الإرسال.");
            textInput.reportValidity();
            textInput.oninput = function() { this.setCustomValidity(''); };
        }
        return false;
    }

    if (attachedFiles.length > 0) {
        const fileInput = document.getElementById('attachmentInput');
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(attachedFiles[0].file);
        fileInput.files = dataTransfer.files;
    }

    return true;
}

// دوال معاينة الملفات (تم دمج التكرارات في دالة واحدة موحدة)[cite: 1]
function showFileName(input, previewId, nameTextId) {
    if (input.files && input.files[0]) {
        const textEl = document.getElementById(nameTextId);
        if (textEl) textEl.textContent = input.files[0].name;
        const previewEl = document.getElementById(previewId);
        if (previewEl) {
            previewEl.classList.remove('d-none');
            previewEl.classList.add('d-flex');
        }
    }
}

function removeFile(inputId, previewId) {
    const input = document.getElementById(inputId);
    if (input) input.value = '';
    const preview = document.getElementById(previewId);
    if (preview) {
        preview.classList.add('d-none');
        preview.classList.remove('d-flex');
    }
}

function showEditPreview(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const isImage = file.type.startsWith('image/');
        
        const editFileBox = document.getElementById('editFilePreviewBox');
        const editImageBox = document.getElementById('editImagePreviewBox');
        const editFileNameText = document.getElementById('editFileNameText');
        const editImageThumbnail = document.getElementById('editImageThumbnail');
        const noAttachmentAddBox = document.getElementById('noAttachmentAddBox');
        const removeAttachmentActionBox = document.getElementById('removeAttachmentActionBox');
        const editAttachmentLabel = document.getElementById('editAttachmentLabel');
        
        document.getElementById('removeAttachmentFlag').value = '0';
        editAttachmentLabel.textContent = "المرفق الجديد:";

        if (isImage) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (editImageThumbnail) editImageThumbnail.src = e.target.result;
                if (editImageBox) editImageBox.classList.remove('d-none');
                if (editFileBox) editFileBox.classList.add('d-none');
            }
            reader.readAsDataURL(file);
        } else {
            if (editFileNameText) editFileNameText.textContent = file.name;
            if (editFileBox) editFileBox.classList.remove('d-none');
            if (editImageBox) editImageBox.classList.add('d-none');
        }
        if (noAttachmentAddBox) noAttachmentAddBox.classList.add('d-none');
        if (removeAttachmentActionBox) removeAttachmentActionBox.classList.remove('d-none');
    }
}

function removeCurrentAttachment() {
    const editAttachmentInput = document.getElementById('editAttachmentInput');
    const removeAttachmentFlag = document.getElementById('removeAttachmentFlag');
    if (editAttachmentInput) editAttachmentInput.value = '';
    if (removeAttachmentFlag) removeAttachmentFlag.value = '1';
    
    const editFileBox = document.getElementById('editFilePreviewBox');
    const editImageBox = document.getElementById('editImagePreviewBox');
    const noAttachmentAddBox = document.getElementById('noAttachmentAddBox');
    const removeAttachmentActionBox = document.getElementById('removeAttachmentActionBox');
    const editAttachmentLabel = document.getElementById('editAttachmentLabel');
    const addOrChangeBtnText = document.getElementById('addOrChangeBtnText');

    if (editFileBox) editFileBox.classList.add('d-none');
    if (editImageBox) editImageBox.classList.add('d-none');
    if (removeAttachmentActionBox) removeAttachmentActionBox.classList.add('d-none');
    if (noAttachmentAddBox) noAttachmentAddBox.classList.remove('d-none');
    if (editAttachmentLabel) editAttachmentLabel.textContent = "إرفاق ملف أو صورة (اختياري):";
    if (addOrChangeBtnText) addOrChangeBtnText.textContent = "إضافة ملف أو صورة جديدة";
}

/* ==========================================
   10. إدارة الموظفين (Employee Operations)[cite: 1]
========================================== */
function prepareAddEmployeeModal(storeRoute) {
    const modalTitle    = document.getElementById('employeeModalTitle');
    const form          = document.getElementById('employeeForm');
    const methodInput   = document.getElementById('employeeFormMethod');
    const nameInput     = document.getElementById('employeeNameInput');
    const roleInput     = document.getElementById('employeeRoleInput');
    const passwordGroup = document.getElementById('employeePasswordGroup');
    const passwordInput = document.getElementById('employeePasswordInput');

    if (modalTitle) modalTitle.innerText = 'إضافة موظف جديد';
    if (form) {
        form.reset();
        if (storeRoute) form.action = storeRoute;
    }
    if (methodInput) methodInput.value = 'POST';

    // Add mode -> users.store expects 'username', not 'name'
    if (nameInput) nameInput.setAttribute('name', 'username');

    // Add mode -> submit role=employee (enabled hidden input)
    if (roleInput) roleInput.removeAttribute('disabled');

    // Add mode -> reveal + enable password
    if (passwordGroup) passwordGroup.classList.remove('d-none');
    if (passwordInput) {
        passwordInput.removeAttribute('disabled');
        passwordInput.setAttribute('required', 'required');
    }

    if (document.getElementById('departmentInput'))     document.getElementById('departmentInput').value = '';
    if (document.getElementById('employeeEmailInput'))  document.getElementById('employeeEmailInput').value = '';
    if (document.getElementById('employeePhoneInput'))  document.getElementById('employeePhoneInput').value = '';

    const modalEl = document.getElementById('employeeModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}



function openEditEmployeeModal(button, updateRoute) {
    const row = button.closest('[data-employee-id]');
    if (!row) return;

    const nameInput     = document.getElementById('employeeNameInput');
    const roleInput     = document.getElementById('employeeRoleInput');
    const passwordGroup = document.getElementById('employeePasswordGroup');
    const passwordInput = document.getElementById('employeePasswordInput');

    document.getElementById('employeeModalTitle').innerText = 'تعديل بيانات الموظف';
    document.getElementById('employeeForm').action = updateRoute;
    document.getElementById('employeeFormMethod').value = 'PUT';

    if (nameInput) {
        nameInput.setAttribute('name', 'name');
        nameInput.value = row.getAttribute('data-employee-name') || '';
    }
    if (roleInput) roleInput.setAttribute('disabled', 'disabled');
    if (passwordGroup) passwordGroup.classList.add('d-none');
    if (passwordInput) {
        passwordInput.setAttribute('disabled', 'disabled');
        passwordInput.removeAttribute('required');
        passwordInput.value = '';
    }
    if (document.getElementById('departmentInput'))    document.getElementById('departmentInput').value = row.getAttribute('data-department') || '';
    if (document.getElementById('employeeEmailInput')) document.getElementById('employeeEmailInput').value = row.getAttribute('data-employee-email') || '';
    if (document.getElementById('employeePhoneInput')) document.getElementById('employeePhoneInput').value = row.getAttribute('data-employee-phone') || '';

    const modalEl = document.getElementById('employeeModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function openDeleteEmployeeModal(button, destroyRoute) {
    const row = button.closest('[data-employee-id]');
    const name = row ? (row.getAttribute('data-employee-name') || 'الموظف') : 'الموظف';

    const textEl = document.getElementById('deleteEmployeeModalText');
    if (textEl) textEl.innerText = 'هل تريد حذف الموظف (' + name + ')؟';

    const form = document.getElementById('deleteEmployeeForm');
    if (form) form.action = destroyRoute;

    const modalEl = document.getElementById('deleteEmployeeModal');
    if (modalEl) {
        let instance = bootstrap.Modal.getInstance(modalEl);
        if (!instance) instance = new bootstrap.Modal(modalEl);
        instance.show();
    }
}

function openEmployeeProjectStatusModal(projectId, currentStatus, updateUrl) {
    const form = document.getElementById('employeeProjectStatusForm');
    if (form) form.action = updateUrl;
    const statusSelect = document.getElementById('employeeProjectStatusSelect');
    if (statusSelect) statusSelect.value = currentStatus;
    
    var myModal = new bootstrap.Modal(document.getElementById('employeeProjectStatusModal'));
    myModal.show();
}

// دالة موحدة لتحديث حالة مهمة الموظف (تم إزالة التكرار المطابق)[cite: 1]
function openEmployeeTaskStatusModal(taskId, currentStatus, updateUrl) {
    const form = document.getElementById('employeeTaskStatusForm');
    if (form) form.action = updateUrl;
    const statusSelect = document.getElementById('employeeTaskStatusSelect');
    if (statusSelect) statusSelect.value = currentStatus;
    
    var myModal = new bootstrap.Modal(document.getElementById('employeeTaskStatusModal'));
    myModal.show();
}

/* ==========================================
   Stage Filters (status + priority, per stage tab)
========================================== */
function applyStageFilters(targetId) {
    const container = document.getElementById(targetId);
    if (!container) return;

    const statusSelect = document.querySelector(
        `.stage-filter[data-filter-type="status"][data-stage-target="${targetId}"]`
    );
    const prioritySelect = document.querySelector(
        `.stage-filter[data-filter-type="priority"][data-stage-target="${targetId}"]`
    );

    const statusValue = statusSelect ? statusSelect.value : '';
    const priorityValue = prioritySelect ? prioritySelect.value : '';

    container.querySelectorAll('.task-row-item').forEach(item => {
        const status = item.getAttribute('data-status') || '';
        const priority = item.getAttribute('data-priority') || '';

        const statusMatch = !statusValue || status === statusValue;
        const priorityMatch = !priorityValue || priority === priorityValue;

        if (statusMatch && priorityMatch) {
            item.style.removeProperty('display');
        } else {
            item.style.setProperty('display', 'none', 'important');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.stage-filter').forEach(select => {
        select.addEventListener('change', function () {
            applyStageFilters(this.getAttribute('data-stage-target'));
        });
    });
});

/* ==========================================
   Reusable List Paginator (Client-side)
   ------------------------------------------------------------
   Usage:
     <div id="myGrid">
         <div class="paginate-item" data-filter-match="1">...</div>
     </div>
     <div id="myPagination" class="pagination-controls"></div>

     const paginator = createListPaginator({
         gridSelector: '#myGrid',
         itemSelector: '.paginate-item',
         controlsId:   'myPagination',
         perPage:      8,
     });
     paginator.render();          // initial render
     paginator.reset();           // after filter change (resets to page 1)
========================================== */
function createListPaginator(config) {
    const perPage = config.perPage || 8;
    let currentPage = 1;

    const grid = document.querySelector(config.gridSelector);
    const controls = document.getElementById(config.controlsId);

    if (!grid || !controls) {
        console.warn('Paginator: grid or controls not found.', config);
        return { render() {}, reset() {}, goToPage() {} };
    }

    const allItems = () => Array.from(grid.querySelectorAll(config.itemSelector));
    const matchedItems = () => allItems().filter(el => el.getAttribute('data-filter-match') !== '0');

    function render() {
        const matched = matchedItems();
        const totalPages = Math.max(1, Math.ceil(matched.length / perPage));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        // Hide every row, then reveal only the current page slice.
        // Using setProperty('display','none','important') to defeat Bootstrap's
        // .d-flex !important if it's ever added to a row.
        allItems().forEach(el => el.style.setProperty('display', 'none', 'important'));
        const start = (currentPage - 1) * perPage;
        matched.slice(start, start + perPage).forEach(el => el.style.removeProperty('display'));

        renderControls(totalPages, matched.length);
    }

    // Builds what to render between the arrows.
    // Numbers become buttons; gaps become '...'.
    // Examples (total=12):
    //   current=1  -> [1, 2, 3, '...', 12]
    //   current=6  -> [1, '...', 5, 6, 7, '...', 12]
    //   current=12 -> [1, '...', 11, 12]
    function buildPageList(current, total) {
        if (total <= 5) {
            return Array.from({ length: total }, (_, i) => i + 1);
        }

        const pages = new Set([1, total]);
        for (let offset = -1; offset <= 1; offset++) {
            const p = current + offset;
            if (p >= 1 && p <= total) pages.add(p);
        }

        const sorted = [...pages].sort((a, b) => a - b);
        const result = [];
        let prev = 0;
        for (const p of sorted) {
            if (p - prev === 2) {
                // Single missing page: show it instead of a wasteful ellipsis
                result.push(prev + 1);
            } else if (p - prev > 2) {
                result.push('...');
            }
            result.push(p);
            prev = p;
        }
        return result;
    }

    function renderControls(totalPages, totalItems) {
        if (totalItems === 0 || totalPages <= 1) {
            controls.innerHTML = '';
            return;
        }

        const prevDisabled = currentPage === 1;
        const nextDisabled = currentPage === totalPages;
        const list = buildPageList(currentPage, totalPages);

        let html = `
            <button type="button" class="page-btn ${prevDisabled ? 'disabled' : ''}" data-page="${currentPage - 1}" ${prevDisabled ? 'disabled' : ''} aria-label="السابق">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        `;

        for (const item of list) {
            if (item === '...') {
                html += `<span class="page-ellipsis">…</span>`;
            } else {
                const active = item === currentPage;
                html += `<button type="button" class="page-btn ${active ? 'active' : ''}" data-page="${item}" ${active ? 'aria-current="page"' : ''}>${item}</button>`;
            }
        }

        html += `
            <button type="button" class="page-btn ${nextDisabled ? 'disabled' : ''}" data-page="${currentPage + 1}" ${nextDisabled ? 'disabled' : ''} aria-label="التالي">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
        `;

        controls.innerHTML = html;

        controls.querySelectorAll('.page-btn:not(.disabled):not(.active)').forEach(btn => {
            btn.addEventListener('click', () => {
                const page = parseInt(btn.getAttribute('data-page'), 10);
                if (page >= 1 && page <= totalPages) {
                    currentPage = page;
                    render();
                    grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }

    return {
        render,
        reset()   { currentPage = 1; render(); },
        goToPage(page) { currentPage = page; render(); },
    };
}
/* Manager Operations (Team page) */
function openEditManagerModal(button, updateUrl) {
    const row = button.closest('[data-manager-id]');
    if (!row) return;

    const username = row.getAttribute('data-manager-username') || '';
    const email    = row.getAttribute('data-manager-email') || '';
    const phone    = row.getAttribute('data-manager-phone') || '';

    document.getElementById('managerModalTitle').innerText = 'تعديل بيانات المدير';
    const form = document.getElementById('managerForm');
    if (form) form.action = updateUrl;

    if (document.getElementById('managerNameInput'))  document.getElementById('managerNameInput').value = username;
    if (document.getElementById('managerEmailInput')) document.getElementById('managerEmailInput').value = email;
    if (document.getElementById('managerPhoneInput')) document.getElementById('managerPhoneInput').value = phone;
    if (document.getElementById('managerPasswordInput')) document.getElementById('managerPasswordInput').value = '';

    const modalEl = document.getElementById('managerModal');
    if (modalEl) {
        let instance = bootstrap.Modal.getInstance(modalEl);
        if (!instance) instance = new bootstrap.Modal(modalEl);
        instance.show();
    }
}

function openDeleteManagerModal(button, deleteUrl) {
    const row = button.closest('[data-manager-id]');
    const username = row ? (row.getAttribute('data-manager-username') || 'المدير') : 'المدير';

    const textEl = document.getElementById('deleteManagerModalText');
    if (textEl) textEl.innerText = 'هل تريد حذف المدير (' + username + ')؟';

    const form = document.getElementById('deleteManagerForm');
    if (form) form.action = deleteUrl;

    const modalEl = document.getElementById('deleteManagerModal');
    if (modalEl) {
        let instance = bootstrap.Modal.getInstance(modalEl);
        if (!instance) instance = new bootstrap.Modal(modalEl);
        instance.show();
    }  
}

/* ==========================================
   Project List Popovers (Team page count chips)
========================================== */
function initProjectListPopovers() {
    document.querySelectorAll('.project-count-trigger').forEach(function (trigger) {
        if (trigger.dataset.popoverInit === '1') return;
        trigger.dataset.popoverInit = '1';

        let projects = [];
        try {
            projects = JSON.parse(trigger.getAttribute('data-projects') || '[]');
        } catch (e) {
            projects = [];
        }

        if (!projects.length) return;

        const html = projects
            .map(p => '<a href="/projects/' + encodeURIComponent(p.id) + '" class="project-popover-link">' + escapeHtml(p.name) + '</a>')
            .join('');

        new bootstrap.Popover(trigger, {
            html: true,
            content: html,
            trigger: 'manual',
            placement: 'bottom',
            container: 'body',
            sanitize: false,
            customClass: 'project-list-popover',
        });
    });

    // Toggle a popover on its own trigger; close any other open popover.
    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('.project-count-trigger');

        // Close every open popover first (only one at a time)
        document.querySelectorAll('.project-count-trigger').forEach(function (t) {
            if (t === trigger) return;
            const inst = bootstrap.Popover.getInstance(t);
            if (inst) inst.hide();
        });

        if (trigger) {
            const inst = bootstrap.Popover.getInstance(trigger);
            if (inst) {
                inst.toggle();
            }
            return;
        }

        // Click was outside any trigger — close everything,
        // unless the click was inside an open popover (so links inside work).
        if (!e.target.closest('.popover')) {
            document.querySelectorAll('.project-count-trigger').forEach(function (t) {
                const inst = bootstrap.Popover.getInstance(t);
                if (inst) inst.hide();
            });
        }
    });

    // Escape closes any open popover.
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.project-count-trigger').forEach(function (t) {
            const inst = bootstrap.Popover.getInstance(t);
            if (inst) inst.hide();
        });
    });
}

/* ==========================================
   Admin Dashboard — AJAX Section Filters
========================================== */
(function () {
    function currentParams(sectionKey, rangeKey, fromKey, toKey) {
        // Merge the other section's URL params with this section's new ones
        const url = new URL(window.location.href);
        const params = new URLSearchParams();
        // Keep whatever exists in the URL for the OTHER section
        ['pipeline_range','pipeline_from','pipeline_to','activity_range','activity_from','activity_to'].forEach(k => {
            if (url.searchParams.has(k) && !k.startsWith(sectionKey)) {
                params.set(k, url.searchParams.get(k));
            }
        });
        params.set(rangeKey, document.getElementById(rangeKey === 'pipeline_range' ? 'pipelineRangeSelect' : 'activityRangeSelect').value);
        const custom = document.getElementById(rangeKey === 'pipeline_range' ? 'pipelineCustomRange' : 'activityCustomRange');
        if (!custom.classList.contains('d-none')) {
            const fromEl = custom.querySelector('input[name="from"]');
            const toEl   = custom.querySelector('input[name="to"]');
            if (fromEl && fromEl.value) params.set(fromKey, fromEl.value);
            if (toEl   && toEl.value)   params.set(toKey, toEl.value);
        }
        return params;
    }

    function syncUrl(params) {
        const url = new URL(window.location.href);
        // Replace all filter-related params
        ['pipeline_range','pipeline_from','pipeline_to','activity_range','activity_from','activity_to'].forEach(k => {
            url.searchParams.delete(k);
        });
        for (const [k, v] of params.entries()) url.searchParams.set(k, v);
        window.history.replaceState({}, '', url);
    }

    // --- Pipeline ---
    const pipelineForm   = document.getElementById('pipelineFilterForm');
    const pipelineSelect = document.getElementById('pipelineRangeSelect');
    const pipelineCustom = document.getElementById('pipelineCustomRange');

    async function loadPipeline() {
        if (!pipelineForm) return;
        const params = currentParams('pipeline', 'pipeline_range', 'pipeline_from', 'pipeline_to');
        const card = document.getElementById('pipelineChartCard');
        card.classList.add('is-loading');

        try {
            const res = await fetch(`/dashboard/pipeline-data?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();

            const chart = Chart.getChart('pipelineChart');
            if (chart) {
                chart.data.labels = data.labels;
                chart.data.datasets[0].data = data.counts;
                chart.data.datasets[0].backgroundColor = data.colors;
                chart.data.datasets[0].borderColor = data.colors;
                chart.update();
            }
            const badge = document.getElementById('pipelineTotalBadge');
            if (badge) badge.textContent = `إجمالي ${data.total} مشروعاً`;
            syncUrl(params);
        } catch (e) {
            console.error('Pipeline fetch failed:', e);
        } finally {
            card.classList.remove('is-loading');
        }
    }

    if (pipelineSelect) {
        pipelineSelect.addEventListener('change', function () {
            if (this.value === 'custom') {
                pipelineCustom.classList.remove('d-none');
            } else {
                pipelineCustom.classList.add('d-none');
                loadPipeline();
            }
        });
    }
    if (pipelineForm) {
        pipelineForm.addEventListener('submit', function (e) {
            e.preventDefault();
            loadPipeline();
        });
    }

    // --- Activity ---
    const activityForm   = document.getElementById('activityFilterForm');
    const activitySelect = document.getElementById('activityRangeSelect');
    const activityCustom = document.getElementById('activityCustomRange');
    const activityTimeline = document.getElementById('activityTimeline');

    async function loadActivity() {
        if (!activityForm) return;
        const params = currentParams('activity', 'activity_range', 'activity_from', 'activity_to');
        const card = document.getElementById('activityFeedCard');
        card.classList.add('is-loading');

        try {
            const res = await fetch(`/dashboard/activity-feed?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            activityTimeline.innerHTML = await res.text();
            syncUrl(params);
        } catch (e) {
            console.error('Activity fetch failed:', e);
        } finally {
            card.classList.remove('is-loading');
        }
    }

    if (activitySelect) {
        activitySelect.addEventListener('change', function () {
            if (this.value === 'custom') {
                activityCustom.classList.remove('d-none');
            } else {
                activityCustom.classList.add('d-none');
                loadActivity();
            }
        });
    }
    if (activityForm) {
        activityForm.addEventListener('submit', function (e) {
            e.preventDefault();
            loadActivity();
        });
    }
})();