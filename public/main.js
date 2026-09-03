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
});

/* ==========================================
   2. إدارة عمليات المشاريع (Projects Operations)[cite: 1]
========================================== */
function prepareAddProjectModal(storeUrl) {
    const modalTitle = document.getElementById('projectModalTitle');
    const projectForm = document.getElementById('projectForm');
    const methodInput = document.getElementById('projectFormMethod');

    if (modalTitle) modalTitle.innerText = "إضافة مشروع جديد";
    if (projectForm) {
        projectForm.reset();
        if (storeUrl) projectForm.action = storeUrl;
    }
    if (methodInput) methodInput.value = "POST";

    const companyInput = document.getElementById('projectCompanyNameInput');
    if (companyInput) companyInput.value = '';

    const modalEl = document.getElementById('projectModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function openEditProjectModal(button, updateUrl) {
    const projectCard = button.closest('.project-card-wrapper');
    const modalTitle = document.getElementById('projectModalTitle');
    const projectForm = document.getElementById('projectForm');
    const methodInput = document.getElementById('projectFormMethod');

    if (modalTitle) modalTitle.innerText = "تعديل المشروع";
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

    const modalEl = document.getElementById('projectModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
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

function prepareAddModal() {
    const modalTitle = document.getElementById('taskModalTitle');
    const taskForm = document.getElementById('taskForm');
    const methodInput = document.getElementById('taskFormMethod');
    
    if (modalTitle) modalTitle.innerText = "إضافة مهمة";
    if (taskForm) {
        taskForm.reset();
        taskForm.action = "/tasks";
    }
    if (methodInput) methodInput.value = "POST";

    const startDateInput = document.getElementById('startDateInput');
    const endDateInput = document.getElementById('endDateInput');
    if (startDateInput) { startDateInput.removeAttribute('min'); startDateInput.removeAttribute('max'); }
    if (endDateInput) { endDateInput.removeAttribute('min'); endDateInput.removeAttribute('max'); }
}

function openEditModal(button) {
    const taskCard = button.closest('.task-card');
    if (!taskCard) return;

    const taskId = taskCard.getAttribute('data-task-id');
    const taskTitle = taskCard.getAttribute('data-task-title') || '';
    const projectId = taskCard.getAttribute('data-project-id') || '';
    const assignedTo = taskCard.getAttribute('data-assigned-to') || '';
    const description = taskCard.getAttribute('data-description') || '';
    const startDate = taskCard.getAttribute('data-start-date') || '';
    const endDate = taskCard.getAttribute('data-end-date') || '';
    const status = taskCard.getAttribute('data-status') || '';
    const companyName = taskCard.getAttribute('data-company') || '';

    const modalTitle = document.getElementById('taskModalTitle');
    const taskForm = document.getElementById('taskForm');
    const methodInput = document.getElementById('taskFormMethod');

    if (modalTitle) modalTitle.innerText = "تعديل المهمة";
    if (taskForm) taskForm.action = `/tasks/${taskId}`;
    if (methodInput) methodInput.value = "PUT";

    if (document.getElementById('taskNameInput')) document.getElementById('taskNameInput').value = taskTitle;
    
    if (document.getElementById('projectIdInput')) {
        document.getElementById('projectIdInput').value = projectId;
        updateProjectDatesLimits();
    }

    if (document.getElementById('companyNameInput') && companyName) {
        document.getElementById('companyNameInput').value = companyName;
    }

    if (document.getElementById('assignedToInput')) document.getElementById('assignedToInput').value = assignedTo;
    if (document.getElementById('descriptionInput')) document.getElementById('descriptionInput').value = description;
    
    if (document.getElementById('startDateInput')) {
        document.getElementById('startDateInput').value = startDate ? startDate.split('T')[0] : '';
    }
    if (document.getElementById('endDateInput')) {
        document.getElementById('endDateInput').value = endDate ? endDate.split('T')[0] : '';
    }
    if (document.getElementById('statusSelect')) document.getElementById('statusSelect').value = status;

    const modalEl = document.getElementById('taskModal');
    if (modalEl) {
        let modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (!modalInstance) modalInstance = new bootstrap.Modal(modalEl);
        modalInstance.show();
    }
}

function openDeleteModal(button) {
    const taskCard = button.closest('.task-card');
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

/* tasks page*/
function prepareAddClientModal(storeUrl) {
    const modalTitle = document.getElementById('clientModalTitle');
    const clientForm = document.getElementById('clientForm');
    const methodInput = document.getElementById('clientFormMethod');

    if (modalTitle) modalTitle.innerText = "إضافة عميل جديد";
    if (clientForm) {
        clientForm.reset();
        if (storeUrl) clientForm.action = storeUrl;
    }
    if (methodInput) methodInput.value = "POST";

    const checkboxContainer = document.getElementById('clientProjectsCheckboxes');
    if (checkboxContainer) {
        checkboxContainer.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
    }

    const modalEl = document.getElementById('clientModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function openEditClientModal(button, updateUrl) {
    const clientCard = button.closest('.client-card-wrapper');
    const modalTitle = document.getElementById('clientModalTitle');
    const clientForm = document.getElementById('clientForm');
    const methodInput = document.getElementById('clientFormMethod');

    if (modalTitle) modalTitle.innerText = "تعديل بيانات العميل";
    if (clientForm && updateUrl) clientForm.action = updateUrl;
    if (methodInput) methodInput.value = "PUT";

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

    const modalEl = document.getElementById('clientModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function openDeleteClientModal(button, deleteUrl) {
    const clientCard = button.closest('.client-card-wrapper');
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

function openDeleteAccountModal() {
    const nameInput = document.getElementById('profileNameInput');
    const accountName = (nameInput && nameInput.value.trim() !== "") ? nameInput.value : "المستخدم"; 
    
    const deleteText = document.getElementById('deleteAccountModalText');
    if (deleteText) deleteText.innerText = `هل تريد حذف حساب ${accountName} ؟`;
    
    const modalEl = document.getElementById('deleteAccountModal');
    if (modalEl) {
        const deleteModal = new bootstrap.Modal(modalEl);
        deleteModal.show();
    }
}

function confirmDeleteAccount() {
    const deleteModalEl = document.getElementById('deleteAccountModal');
    if (deleteModalEl) {
        const deleteModalInstance = bootstrap.Modal.getInstance(deleteModalEl);
        if (deleteModalInstance) deleteModalInstance.hide();
    }
    showStatusMessage("تم حذف الحساب بنجاح");
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
    document.getElementById('employeeModalTitle').innerText = 'إضافة موظف جديد';
    document.getElementById('employeeForm').action = storeRoute;
    document.getElementById('employeeFormMethod').value = 'POST';
    document.getElementById('employeeNameInput').value = '';
    document.getElementById('departmentInput').value = '';
    document.getElementById('employeeEmailInput').value = '';
    document.getElementById('employeePhoneInput').value = '';
    
    var myModal = new bootstrap.Modal(document.getElementById('employeeModal'));
    myModal.show();
}

function openEditEmployeeModal(button, updateRoute) {
    var wrapper = button.closest('.employee-card-wrapper');
    var name = wrapper.getAttribute('data-employee-name');
    var department = wrapper.getAttribute('data-department');
    var email = wrapper.getAttribute('data-employee-email');
    var phone = wrapper.getAttribute('data-employee-phone');

    document.getElementById('employeeModalTitle').innerText = 'تعديل بيانات الموظف';
    document.getElementById('employeeForm').action = updateRoute;
    document.getElementById('employeeFormMethod').value = 'PUT';
    document.getElementById('employeeNameInput').value = name;
    document.getElementById('departmentInput').value = department;
    document.getElementById('employeeEmailInput').value = email;
    document.getElementById('employeePhoneInput').value = phone;

    var myModal = new bootstrap.Modal(document.getElementById('employeeModal'));
    myModal.show();
}

function openDeleteEmployeeModal(button, destroyRoute) {
    var wrapper = button.closest('.employee-card-wrapper');
    var name = wrapper.getAttribute('data-employee-name');
    
    document.getElementById('deleteEmployeeModalText').innerText = 'هل تريد حذف الموظف (' + name + ')؟';
    document.getElementById('deleteEmployeeForm').action = destroyRoute;

    var myModal = new bootstrap.Modal(document.getElementById('deleteEmployeeModal'));
    myModal.show();
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