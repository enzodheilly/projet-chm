// ============================================================
//  MODAL REGISTER — VERSION OPTIMISÉE COMPLÈTE
// ============================================================

// --- Gestion ouverture / fermeture du modal ---
(function () {
    const modal = document.getElementById("registerModal");
    if (!modal) return;

    const openBtns = document.querySelectorAll(".js-open-register-modal");
    const closeBtn = document.getElementById("closeRegisterModal");

    const openModal = (e) => {
        if (e) e.preventDefault();
        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        document.documentElement.style.overflow = "hidden";
        closeBtn?.focus();
    };

    const closeModal = () => {
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
        document.documentElement.style.overflow = "";
    };

    // open buttons
    openBtns.forEach((b) => b.addEventListener("click", openModal));

    // close button
    closeBtn?.addEventListener("click", closeModal);

    // prevent overlay closing
    modal.addEventListener("click", (e) => {
        const card = modal.querySelector(".modal-card");
        if (!card.contains(e.target)) {
            e.stopPropagation();
        }
    });

    // ESC key
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && modal.classList.contains("is-open")) {
            e.preventDefault();
            closeModal();
        }
    });
})();

// ============================================================
// NAVIGATION ENTRE ÉTAPES : social → email → verify → login
// ============================================================

document.addEventListener("DOMContentLoaded", () => {
    const stepSocial = document.getElementById("modal-step-social");
    const stepEmail = document.getElementById("modal-step-email");
    const stepVerify = document.getElementById("modal-step-verify");
    const stepLogin = document.getElementById("modal-step-login");

    const switchToEmail = document.getElementById("js-switch-to-email");
    const backToSocial = document.getElementById("js-back-to-social");
    const backToEmail = document.getElementById("js-back-to-email");

    // restauration auto étape "verify"
    if (sessionStorage.getItem("registerStep") === "verify") {
        stepSocial.style.display = "none";
        stepEmail.style.display = "none";
        stepVerify.style.display = "block";
    }

    if (switchToEmail) {
        switchToEmail.addEventListener("click", (e) => {
            e.preventDefault();
            stepSocial.style.display = "none";
            stepEmail.style.display = "block";
            sessionStorage.removeItem("registerStep");
        });
    }

    if (backToSocial) {
        backToSocial.addEventListener("click", (e) => {
            e.preventDefault();
            stepEmail.style.display = "none";
            stepSocial.style.display = "block";
            sessionStorage.removeItem("registerStep");
        });
    }

    if (backToEmail) {
        backToEmail.addEventListener("click", (e) => {
            e.preventDefault();
            stepVerify.style.display = "none";
            stepEmail.style.display = "block";
            sessionStorage.removeItem("registerStep");
        });
    }

    // passage vers login
    const loginLinks = document.querySelectorAll(".js-open-login-modal");
    const backToSocialLogin = document.getElementById("js-back-to-social-login");

    loginLinks.forEach((l) =>
        l.addEventListener("click", (e) => {
            e.preventDefault();
            stepSocial.style.display = "none";
            stepLogin.style.display = "block";
        })
    );

    backToSocialLogin?.addEventListener("click", (e) => {
        e.preventDefault();
        stepLogin.style.display = "none";
        stepSocial.style.display = "block";
    });
});

// ============================================================
//  FORMULAIRE D’INSCRIPTION — VALIDATIONS + AJAX + TURNSTILE
// ============================================================

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("emailRegisterForm");
    if (!form) return;

    const errorBox = document.getElementById("form-error-message");
    const submitButton = form.querySelector(".btn-register");
    const btnText = submitButton?.querySelector(".btn-text");
    const btnSpinner = submitButton?.querySelector(".btn-spinner");

    const showFormError = (msg) => {
        errorBox.innerHTML = msg;
        errorBox.style.display = "block";
        errorBox.classList.add("shake");
        setTimeout(() => errorBox.classList.remove("shake"), 600);
    };

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const terms = form.querySelector('input[name="registration_form[acceptedTerms]"]');
        if (!terms?.checked) {
            showFormError("⚠️ Vous devez accepter les conditions générales.");
            terms.focus();
            return;
        }

        const captcha = document.querySelector('input[name="cf-turnstile-response"]')?.value;
        if (!captcha) {
            showFormError("⚠️ Veuillez passer la vérification anti-robot.");
            if (typeof turnstile !== "undefined") turnstile.reset();
            return;
        }

        // UI loading
        submitButton.disabled = true;
        btnText.style.display = "none";
        btnSpinner.style.display = "inline-flex";

        const formData = new FormData(form);

        try {
            const res = await fetch(form.action, { method: "POST", body: formData });
            const json = await res.json();

            if (json.success) {
                // move to verification step
                document.getElementById("modal-step-email").style.display = "none";
                document.getElementById("modal-step-verify").style.display = "block";
                sessionStorage.setItem("registerStep", "verify");

                if (typeof turnstile !== "undefined") turnstile.reset();
            } else {
                let errors = json.errors || [json.message || "Erreur inconnue"];

                // format message
                showFormError("<ul>" + errors.map((e) => `<li>${e}</li>`).join("") + "</ul>");

                if (typeof turnstile !== "undefined") turnstile.reset();
            }
        } catch {
            showFormError("⚠️ Erreur serveur. Réessayez plus tard.");
        }

        // reset button
        submitButton.disabled = false;
        btnText.style.display = "inline";
        btnSpinner.style.display = "none";
    });
});

// ============================================================
//  SYSTÈME DE VÉRIFICATION DU CODE À 6 CHIFFRES
// ============================================================

document.addEventListener("DOMContentLoaded", () => {
    const formVerify = document.getElementById("verify-form");
    if (!formVerify) return;

    const submitBtn = document.getElementById("verifyButton");
    const btnText = submitBtn?.querySelector(".btn-text");
    const btnSpinner = submitBtn?.querySelector(".btn-spinner");
    const messageBox = document.getElementById("verifyMessage");

    const codeInputs = document.querySelectorAll(".code-input");
    const hiddenInput = document.getElementById("verify-full-code");

    // --- Mise à jour du champ caché ---
    const updateHidden = () => {
        hiddenInput.value = [...codeInputs].map((i) => i.value).join("");
    };

    // --- Gestion des 6 cases ---
    codeInputs.forEach((input, index) => {
        input.addEventListener("input", (e) => {
            e.target.value = e.target.value.replace(/\D/g, "");

            if (e.target.value.length === 1 && index < 5) {
                codeInputs[index + 1].focus();
            }
            updateHidden();
        });

        input.addEventListener("keydown", (e) => {
            if (e.key === "Backspace" && !e.target.value && index > 0) {
                codeInputs[index - 1].focus();
            }
        });

        // Coller un code complet
        input.addEventListener("paste", (e) => {
            e.preventDefault();
            const code = (e.clipboardData.getData("text") || "").replace(/\D/g, "");
            if (code.length === 6) {
                codeInputs.forEach((el, i) => (el.value = code[i] || ""));
                updateHidden();
                codeInputs[5].focus();
            }
        });
    });

    const showVerifyMsg = (msg, type = "") => {
        if (!msg) {
            messageBox.style.display = "none";
            return;
        }
        messageBox.textContent = msg;
        messageBox.className = "verify-message " + type;
        messageBox.style.display = "block";
    };

    // --- Soumission ---
    formVerify.addEventListener("submit", async (e) => {
        e.preventDefault();

        const code = hiddenInput.value.trim();
        if (code.length !== 6) {
            showVerifyMsg("Veuillez entrer un code à 6 chiffres.", "error");
            return;
        }

        btnText.style.display = "none";
        btnSpinner.style.display = "inline-flex";
        submitBtn.disabled = true;

        const formData = new FormData(formVerify);

        try {
            const res = await fetch(formVerify.action, {
                method: "POST",
                body: formData,
                headers: { Accept: "application/json" },
            });
            const data = await res.json();

            if (data.success) {
                showVerifyMsg("", "");

                // Animation étape vérification → login
                const verifyStep = document.getElementById("modal-step-verify");
                const loginStep = document.getElementById("modal-step-login");

                setTimeout(() => {
                    verifyStep.classList.add("fade-out");
                    setTimeout(() => {
                        verifyStep.style.display = "none";
                        verifyStep.classList.remove("fade-out");

                        loginStep.style.display = "block";
                        loginStep.classList.add("fade-in");

                        setTimeout(() => {
                            loginStep.classList.remove("fade-in");
                            btnText.style.display = "inline";
                            btnSpinner.style.display = "none";
                            submitBtn.disabled = false;
                        }, 400);
                    }, 300);
                }, 800);
            } else {
                showVerifyMsg(data.message || "Code invalide.", "error");
                btnText.style.display = "inline";
                btnSpinner.style.display = "none";
                submitBtn.disabled = false;
            }
        } catch {
            showVerifyMsg("⚠️ Erreur serveur. Réessayez plus tard.", "error");
            btnText.style.display = "inline";
            btnSpinner.style.display = "none";
            submitBtn.disabled = false;
        }
    });
});


// ============================================================
//  FORMULAIRE LOGIN (avec CAPTCHA TURNSTILE)
// ============================================================

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("login-form");
    const btn = document.getElementById("loginButton");
    const errorBox = document.getElementById("loginError");
    if (!form || !btn) return;

    const btnText = btn.querySelector(".btn-text");
    const btnSpinner = btn.querySelector(".btn-spinner");

    const showError = (msg) => {
        errorBox.textContent = msg;
        errorBox.style.display = msg ? "block" : "none";
        if (msg) {
            errorBox.classList.add("shake");
            setTimeout(() => errorBox.classList.remove("shake"), 600);
        }
    };

    const resetBtn = () => {
        btnText.style.display = "inline";
        btnSpinner.style.display = "none";
        btn.disabled = false;
    };

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        showError("");

        btnText.style.display = "none";
        btnSpinner.style.display = "inline-flex";
        btn.disabled = true;

        const captcha = form.querySelector('input[name="cf-turnstile-response"]')?.value;
        if (!captcha) {
            showError("⚠️ Veuillez valider le CAPTCHA.");
            resetBtn();
            return;
        }

        try {
            const res = await fetch(form.action, {
                method: "POST",
                body: new FormData(form),
                headers: { Accept: "application/json" },
            });
            const data = await res.json();

            if (data.success) {
                window.location.replace(data.redirect || "/");
                return; // on ne reset pas le bouton (spinner → transition)
            }

            showError(data.message || "Identifiants incorrects.");
            resetBtn();
        } catch {
            showError("⚠️ Erreur serveur.");
            resetBtn();
        }
    });
});

// ============================================================
//  OUVERTURE MODAL MOT DE PASSE OUBLIÉ
// ============================================================

document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("registerModal");

    const stepSocial = document.getElementById("modal-step-social");
    const stepEmail = document.getElementById("modal-step-email");
    const stepVerify = document.getElementById("modal-step-verify");
    const stepLogin = document.getElementById("modal-step-login");
    const stepResetEmail = document.getElementById("modal-step-reset-email");

    const forgotBtn = document.getElementById("js-forgot-password");
    const backToLogin = document.getElementById("js-back-to-login");

    if (!forgotBtn || !stepResetEmail) return;

    // --- OUVRIR L'ÉTAPE RESET EMAIL ---
    forgotBtn.addEventListener("click", (e) => {
        e.preventDefault();

        // cacher les autres étapes
        [
            stepSocial,
            stepEmail,
            stepVerify,
            stepLogin
        ].forEach(step => step && (step.style.display = "none"));

        // afficher l'étape mot de passe oublié
        stepResetEmail.style.display = "block";

        // ouvrir la modale
        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        document.documentElement.style.overflow = "hidden";
    });

    // --- RETOUR À LA CONNEXION ---
    if (backToLogin) {
        backToLogin.addEventListener("click", (e) => {
            e.preventDefault();

            stepResetEmail.style.display = "none";
            stepLogin.style.display = "block";
        });
    }
});


// ============================================================
//  MOT DE PASSE OUBLIÉ — Étape 1 : demande d'email
// ============================================================

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("resetRequestForm");
    if (!form) return;

    const btn = document.getElementById("resetRequestBtn");
    const errorBox = document.getElementById("resetError");

    const btnText = btn.querySelector(".btn-text");
    const spinner = btn.querySelector(".btn-spinner");

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        btnText.style.display = "none";
        spinner.style.display = "inline-flex";
        btn.disabled = true;
        errorBox.style.display = "none";

        try {
            const res = await fetch(form.action, {
                method: "POST",
                headers: { "Content-Type": "application/json", Accept: "application/json" },
                body: JSON.stringify({ email: form.querySelector("#resetEmail")?.value.trim() }),
            });
            const data = await res.json();

            errorBox.textContent = data.message;
            errorBox.className = data.success ? "success" : "error";
            errorBox.style.display = "block";
        } catch {
            errorBox.textContent = "⚠️ Erreur serveur.";
            errorBox.className = "error";
            errorBox.style.display = "block";
        }

        btnText.style.display = "inline";
        spinner.style.display = "none";
        btn.disabled = false;
    });
});


// ============================================================
//  RESET PASSWORD — Étape 2 : nouveau mot de passe
// ============================================================

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("resetNewForm");
    if (!form) return;

    const btn = document.getElementById("resetNewBtn");
    const errorBox = document.getElementById("resetNewError");

    const btnText = btn.querySelector(".btn-text");
    const spinner = btn.querySelector(".btn-spinner");

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const pass1 = document.getElementById("newPassword").value;
        const pass2 = document.getElementById("confirmPassword").value;
        const token = document.getElementById("resetToken").value;

        if (pass1 !== pass2) {
            errorBox.textContent = "Les mots de passe ne correspondent pas.";
            errorBox.classList.add("error");
            errorBox.style.display = "block";
            return;
        }
        if (pass1.length < 12) {
            errorBox.textContent = "Minimum 12 caractères.";
            errorBox.classList.add("error");
            errorBox.style.display = "block";
            return;
        }

        btnText.style.display = "none";
        spinner.style.display = "inline-flex";
        btn.disabled = true;
        errorBox.style.display = "none";

        try {
            const res = await fetch(form.action, {
                method: "POST",
                headers: { "Content-Type": "application/json", Accept: "application/json" },
                body: JSON.stringify({ token, password: pass1 }),
            });
            const data = await res.json();

            if (data.success) {
                const resetStep = document.getElementById("modal-step-reset-new");
                const loginStep = document.getElementById("modal-step-login");

                resetStep.classList.add("fade-out");
                setTimeout(() => {
                    resetStep.style.display = "none";
                    resetStep.classList.remove("fade-out");

                    loginStep.style.display = "block";
                    loginStep.classList.add("fade-in");

                    setTimeout(() => loginStep.classList.remove("fade-in"), 400);
                }, 300);
            } else {
                errorBox.textContent = data.message || "Erreur.";
                errorBox.classList.add("error");
                errorBox.style.display = "block";
            }
        } catch {
            errorBox.textContent = "⚠️ Erreur serveur.";
            errorBox.classList.add("error");
            errorBox.style.display = "block";
        }

        btnText.style.display = "inline";
        spinner.style.display = "none";
        btn.disabled = false;
    });
});

// ============================================================
//  AUTO-OUVERTURE DU MODAL SI resetToken PRÉSENT DANS L’URL
// ============================================================

document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get("resetToken");
    if (!token) return;

    console.log("🔐 Token détecté :", token);

    const modal = document.getElementById("registerModal");
    const tokenInput = document.getElementById("resetToken");

    if (tokenInput) tokenInput.value = token;

    const steps = [
        "modal-step-social",
        "modal-step-email",
        "modal-step-verify",
        "modal-step-login",
        "modal-step-reset-email",
        "modal-step-reset-new"
    ];

    // Cache toutes les étapes
    steps.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = "none";
    });

    // Ouvre la modale
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    document.documentElement.style.overflow = "hidden";

    // Affiche l'étape "nouveau mot de passe"
    const resetStep = document.getElementById("modal-step-reset-new");
    if (resetStep) {
        resetStep.style.display = "block";
        resetStep.classList.add("fade-in");
        setTimeout(() => resetStep.classList.remove("fade-in"), 400);
    }
});


// ============================================================
//  PANNEAU RÈGLES MOT DE PASSE + VALIDATION TEMPS RÉEL
// ============================================================

document.addEventListener("DOMContentLoaded", () => {
    const openBtn = document.getElementById("openPasswordRules");
    const closeBtn = document.getElementById("closePasswordRules");
    const modal = document.getElementById("passwordRulesModal");
    const passwordInput = document.getElementById("register-password");

    if (!openBtn || !closeBtn || !modal || !passwordInput) return;

    // 🔓 Ouvre le panneau
    openBtn.addEventListener("click", (e) => {
        e.preventDefault();
        modal.classList.add("is-visible");
    });

    // ❌ Ferme le panneau
    closeBtn.addEventListener("click", (e) => {
        e.preventDefault();
        modal.classList.remove("is-visible");
    });

    // --- Règles ---
    const rules = {
        length: document.getElementById("rule-length"),
        uppercase: document.getElementById("rule-uppercase"),
        lowercase: document.getElementById("rule-lowercase"),
        number: document.getElementById("rule-number"),
        special: document.getElementById("rule-special")
    };

    // --- Vérification en live ---
    passwordInput.addEventListener("input", (e) => {
        const v = e.target.value;

        rules.length.classList.toggle("valid", v.length >= 12);
        rules.uppercase.classList.toggle("valid", /[A-Z]/.test(v));
        rules.lowercase.classList.toggle("valid", /[a-z]/.test(v));
        rules.number.classList.toggle("valid", /[0-9]/.test(v));
        rules.special.classList.toggle("valid", /[^A-Za-z0-9]/.test(v));
    });
});
