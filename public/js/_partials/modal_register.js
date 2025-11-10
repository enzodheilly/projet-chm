// === Gestion ouverture / fermeture du modal ===
(function () {
  const modal = document.getElementById('registerModal');
  const openBtns = document.querySelectorAll('.js-open-register-modal');
  const closeBtn = document.getElementById('closeRegisterModal');

  function openModal(e) {
    if (e) e.preventDefault();
    if (!modal || !closeBtn) return;

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    closeBtn.focus();
    document.documentElement.style.overflow = 'hidden';
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.documentElement.style.overflow = '';
  }

  // 🔹 Ouvrir la modale
  openBtns.forEach(btn => btn.addEventListener('click', openModal));

  // 🔹 Fermer via la croix
  if (closeBtn) closeBtn.addEventListener('click', closeModal);

  // 🚫 NE PAS fermer si clic sur l’overlay
  // (On bloque juste la propagation pour éviter tout bug de focus)
  if (modal) {
    modal.addEventListener('click', (e) => {
      const card = modal.querySelector('.modal-card');
      if (!card.contains(e.target)) {
        e.stopPropagation(); // empêche toute action sans fermer la modale
      }
    });
  }

  // 🔹 Fermer avec la touche Échap
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal && modal.classList.contains('is-open')) {
      e.preventDefault();
      closeModal();
    }
  });
})();


// === Navigation étapes (social / email / vérification code) ===
document.addEventListener('DOMContentLoaded', () => {
  const stepSocial = document.getElementById('modal-step-social');
  const stepEmail = document.getElementById('modal-step-email');
  const stepVerify = document.getElementById('modal-step-verify');

  const switchToEmail = document.getElementById('js-switch-to-email');
  const backToSocial = document.getElementById('js-back-to-social');
  const backToEmail = document.getElementById('js-back-to-email');

  // 🧠 Vérifie si on doit restaurer l’étape "vérification"
  const savedStep = sessionStorage.getItem('registerStep');
  if (savedStep === 'verify') {
    if (stepSocial) stepSocial.style.display = 'none';
    if (stepEmail) stepEmail.style.display = 'none';
    if (stepVerify) stepVerify.style.display = 'block';
  }

  // 👉 Passage de l’étape Social → Email
  if (switchToEmail && stepSocial && stepEmail) {
    switchToEmail.addEventListener('click', (e) => {
      e.preventDefault();
      stepSocial.style.display = 'none';
      stepEmail.style.display = 'block';
      sessionStorage.removeItem('registerStep'); // reset si on revient au début
    });
  }

  // 👉 Retour de l’étape Email → Social
  if (backToSocial && stepEmail && stepSocial) {
    backToSocial.addEventListener('click', (e) => {
      e.preventDefault();
      stepEmail.style.display = 'none';
      stepSocial.style.display = 'block';
      sessionStorage.removeItem('registerStep'); // reset si on revient au début
    });
  }

  // 👉 Retour de l’étape Vérification → Email
  if (backToEmail && stepVerify && stepEmail) {
    backToEmail.addEventListener('click', (e) => {
      e.preventDefault();
      stepVerify.style.display = 'none';
      stepEmail.style.display = 'block';
      sessionStorage.removeItem('registerStep'); // reset si retour
    });
  }
});


// === Icônes œil (toggle mot de passe) ===
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.toggle-password').forEach(toggle => {
    const targetId = toggle.getAttribute('data-target');
    const input = document.getElementById(targetId);
    const eyeOpen = toggle.querySelector('.eye-open');
    const eyeClosed = toggle.querySelector('.eye-closed');

    if (!input || !eyeOpen || !eyeClosed) return;

    toggle.addEventListener('click', () => {
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      eyeOpen.classList.toggle('hide', !isHidden);
      eyeClosed.classList.toggle('hide', isHidden);
    });
  });
});

// === Formulaire inscription ===
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('emailRegisterForm');
  if (!form) return;

  const errorBox = document.getElementById('form-error-message');
  const submitButton = form.querySelector('.btn-register');
  if (!submitButton) return;

  const btnText = submitButton.querySelector('.btn-text');
  const btnSpinner = submitButton.querySelector('.btn-spinner');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const terms = form.querySelector('input[name="registration_form[acceptedTerms]"]');

    // ✅ Vérifie la case CGU
    if (!terms || !terms.checked) {
      if (errorBox) {
        errorBox.style.display = 'block';
        errorBox.innerHTML = "⚠️ Vous devez accepter les conditions générales pour continuer.";
        errorBox.classList.add('visible', 'shake');
        setTimeout(() => errorBox.classList.remove('shake'), 600);
      }
      if (terms) terms.focus();
      return;
    } else if (errorBox) {
      errorBox.style.display = 'none';
    }

    // ✅ Vérifie le CAPTCHA Turnstile
    const turnstileToken = document.querySelector('input[name="cf-turnstile-response"], textarea[name="cf-turnstile-response"]');
    const tokenValue = turnstileToken ? turnstileToken.value.trim() : '';

    if (!tokenValue) {
      if (errorBox) {
        errorBox.style.display = 'block';
        errorBox.innerHTML = "⚠️ Veuillez confirmer que vous n’êtes pas un robot.";
        errorBox.classList.add('visible', 'shake');
        setTimeout(() => errorBox.classList.remove('shake'), 600);
      }

      // 👇 On force Turnstile à se recharger
      if (typeof turnstile !== "undefined") {
        turnstile.reset();
      }

      return;
    }

    // 🔄 Active le spinner
    submitButton.disabled = true;
    if (btnText) btnText.style.display = 'none';
    if (btnSpinner) btnSpinner.style.display = 'inline-flex';

    const formData = new FormData(form);

    try {
      const response = await fetch(form.action, { method: 'POST', body: formData });
      const result = await response.json();

      // ✅ Nettoie les anciens styles d’erreur
      form.querySelectorAll('input').forEach(el => el.classList.remove('input-error'));

      if (result.success) {
        // ✅ Passage à l’étape de vérification du code
        const emailStep = document.getElementById('modal-step-email');
        const verifyStep = document.getElementById('modal-step-verify');
        if (emailStep && verifyStep) {
          emailStep.style.display = 'none';
          verifyStep.style.display = 'block';
        }

        // 💾 Sauvegarde l’étape actuelle (pour garder la vérification après un refresh)
        sessionStorage.setItem('registerStep', 'verify');

        // ✅ Réinitialise le CAPTCHA après succès
        if (typeof turnstile !== "undefined") {
          turnstile.reset();
        }
      } else {
        let errors = result.errors || [];
        if (typeof result.message === "string" && !errors.length) {
          errors = [result.message];
        }

        // Ignore les erreurs CSRF
        errors = errors.filter(e => !e.toLowerCase().includes('csrf'));

        if (errors.some(e => e.toLowerCase().includes('email'))) {
          const emailInput = form.querySelector('input[name="registration_form[email]"]');
          if (emailInput) emailInput.classList.add('input-error');
        }
        if (errors.some(e => e.toLowerCase().includes('mot de passe') || e.toLowerCase().includes('password'))) {
          const pass1 = form.querySelector('input[name="registration_form[password][first]"]');
          const pass2 = form.querySelector('input[name="registration_form[password][second]"]');
          if (pass1) pass1.classList.add('input-error');
          if (pass2) pass2.classList.add('input-error');
        }

        if (errorBox) {
          if (errors.length > 0) {
            let message = "<ul>";
            errors.forEach(err => {
              message += `<li>${err}</li>`;
            });
            message += "</ul>";
            errorBox.innerHTML = message;
          } else {
            errorBox.innerHTML = "⚠️ Une erreur est survenue. Réessayez.";
          }

          errorBox.style.display = 'block';
          errorBox.classList.add('shake');
          setTimeout(() => errorBox.classList.remove('shake'), 600);
        }

        // ❌ Réinitialise le CAPTCHA si erreur serveur
        if (typeof turnstile !== "undefined") {
          turnstile.reset();
        }
      }
    } catch (error) {
      if (errorBox) {
        errorBox.innerHTML = "⚠️ Erreur serveur. Réessaye plus tard.";
        errorBox.style.display = 'block';
        errorBox.classList.add('shake');
        setTimeout(() => errorBox.classList.remove('shake'), 600);
      }

      if (typeof turnstile !== "undefined") {
        turnstile.reset();
      }
    } finally {
      // 🔁 Réinitialise le bouton
      submitButton.disabled = false;
      if (btnText) btnText.style.display = 'inline';
      if (btnSpinner) btnSpinner.style.display = 'none';
    }
  });
});


// === Vérification du code ===
document.addEventListener('DOMContentLoaded', () => {
  const formVerify = document.getElementById('verify-form');
  if (!formVerify) return;

  const submitBtn = document.getElementById('verifyButton');
  const btnText = submitBtn?.querySelector('.btn-text');
  const btnSpinner = submitBtn?.querySelector('.btn-spinner');
  const messageBox = document.getElementById('verifyMessage'); // juste pour erreurs

  const codeInputs = document.querySelectorAll('.code-input');
  const hiddenInput = document.getElementById('verify-full-code');

  // === Gestion du code à 6 cases ===
  if (codeInputs.length && hiddenInput) {
    codeInputs.forEach((input, index) => {
      input.addEventListener('input', (e) => {
        const value = e.target.value.replace(/\D/g, '');
        e.target.value = value;

        if (value.length === 1 && index < codeInputs.length - 1) {
          codeInputs[index + 1].focus();
        }

        updateHiddenCode();
      });

      input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && index > 0) {
          codeInputs[index - 1].focus();
        }
      });

      input.addEventListener('paste', (e) => {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData)
          .getData('text')
          .replace(/\D/g, '');
        if (pasted.length === 6) {
          codeInputs.forEach((inp, i) => (inp.value = pasted[i] || ''));
          updateHiddenCode();
          codeInputs[5].focus();
        }
      });
    });

    function updateHiddenCode() {
      const code = Array.from(codeInputs)
        .map((i) => i.value)
        .join('');
      hiddenInput.value = code;
    }
  }

// === Soumission du code ===
formVerify.addEventListener('submit', async (e) => {
  e.preventDefault();

  if (!submitBtn || !hiddenInput) return;

  const code = hiddenInput.value.trim();
  if (code.length !== 6) {
    showMessage('Veuillez entrer un code à 6 chiffres complet.', 'error');
    return;
  }

  // Affiche le spinner dès le clic
  if (btnText) btnText.style.display = 'none';
  if (btnSpinner) btnSpinner.style.display = 'inline-flex';
  submitBtn.disabled = true;
  showMessage('', '');

  const formData = new FormData(formVerify);

  try {
    const response = await fetch(formVerify.action, {
      method: 'POST',
      body: formData,
      headers: { Accept: 'application/json' },
    });

    const result = await response.json();

    if (result.success) {
      // ✅ Laisse le spinner tourner pendant toute la transition
      if (messageBox) messageBox.style.display = 'none';

      const verifyModal = document.getElementById('modal-step-verify');
      const loginModal = document.getElementById('modal-step-login');

      // Petite pause pour garder le spinner visible avant la transition
      setTimeout(() => {
        if (verifyModal && loginModal) {
          verifyModal.classList.add('fade-out');
          setTimeout(() => {
            verifyModal.style.display = 'none';
            verifyModal.classList.remove('fade-out');
            loginModal.style.display = 'block';
            loginModal.classList.add('fade-in');

            // ✅ STOP spinner SEULEMENT APRÈS l'animation du login (400ms)
            setTimeout(() => {
              loginModal.classList.remove('fade-in');
              if (btnText) btnText.style.display = 'inline';
              if (btnSpinner) btnSpinner.style.display = 'none';
              submitBtn.disabled = false;
            }, 400); // <== spinner reste actif jusqu’ici
          }, 300);
        }
      }, 1200); // <== spinner tourne pendant cette durée + 700 ms = ~1.9 s total
    } else {
      // ❌ Code incorrect
      showMessage(result.message || '❌ Code invalide.', 'error');
      if (btnText) btnText.style.display = 'inline';
      if (btnSpinner) btnSpinner.style.display = 'none';
      submitBtn.disabled = false;
    }
  } catch (error) {
    showMessage('⚠️ Erreur serveur. Réessayez plus tard.', 'error');
    if (btnText) btnText.style.display = 'inline';
    if (btnSpinner) btnSpinner.style.display = 'none';
    submitBtn.disabled = false;
  }
});


  // === Lien "Renvoyer un code" (AJAX) ===
  const resendLink = document.getElementById('resendCodeLink');
  if (resendLink) {
    resendLink.addEventListener('click', async (e) => {
      e.preventDefault();
      showMessage('', '');
      resendLink.style.pointerEvents = 'none';

      try {
        const response = await fetch(resendLink.href, {
          method: 'GET',
          headers: { Accept: 'application/json' },
        });
        const result = await response.json();

        if (result.success) {
          showMessage(result.message, 'success');
        } else {
          showMessage(result.message, 'error');
        }
      } catch {
        showMessage('⚠️ Erreur serveur. Réessayez plus tard.', 'error');
      } finally {
        setTimeout(() => (resendLink.style.pointerEvents = 'auto'), 2000);
      }
    });
  }

  // === Fonction affichage message ===
  function showMessage(text, type) {
    if (!messageBox) return;
    if (!text) {
      messageBox.style.display = 'none';
      return;
    }

    messageBox.textContent = text;
    messageBox.className = 'verify-message';
    if (type === 'error') {
      messageBox.classList.add('error');
    } else if (type === 'success') {
      messageBox.classList.add('success');
    }
    messageBox.style.display = 'block';
  }
});

// === Navigation vers la connexion ===
document.addEventListener('DOMContentLoaded', () => {
  const stepSocial = document.getElementById('modal-step-social');
  const stepLogin = document.getElementById('modal-step-login');
  const loginLinks = document.querySelectorAll('.js-open-login-modal');
  const backToSocialLogin = document.getElementById('js-back-to-social-login');

  loginLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      if (stepSocial && stepLogin) {
        stepSocial.style.display = 'none';
        stepLogin.style.display = 'block';
      }
    });
  });

  backToSocialLogin?.addEventListener('click', (e) => {
    e.preventDefault();
    if (stepSocial && stepLogin) {
      stepLogin.style.display = 'none';
      stepSocial.style.display = 'block';
    }
  });
});

// === Soumission du formulaire de connexion (avec CAPTCHA Turnstile) ===
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("login-form");
  const button = document.getElementById("loginButton");
  const errorBox = document.getElementById("loginError");

  if (!form || !button) return;

  const btnText = button.querySelector(".btn-text");
  const btnSpinner = button.querySelector(".btn-spinner");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    showError("");
    if (btnText) btnText.style.display = "none";
    if (btnSpinner) btnSpinner.style.display = "inline-flex";
    button.disabled = true;

    const formData = new FormData(form);

    // 🔐 Vérifie que le CAPTCHA Cloudflare est validé
    const captchaResponse = form.querySelector('input[name="cf-turnstile-response"]')?.value;
    if (!captchaResponse) {
      showError("⚠️ Veuillez valider le CAPTCHA avant de continuer.");
      resetButton();
      return;
    }

    try {
      const response = await fetch(form.action, {
        method: "POST",
        body: formData,
        headers: { Accept: "application/json" },
      });

      const data = await response.json().catch(() => null);

      if (!data) {
        showError("⚠️ Réponse invalide du serveur.");
        resetButton();
        return;
      }

      if (data.success) {
        // ✅ Connexion réussie : garder le spinner actif jusqu’à la nouvelle page
        errorBox.style.display = "none";
        window.location.replace(data.redirect || "/");
        // ❗️Pas de resetButton ici — on laisse le spinner tourner
      } else {
        // ⚠️ Erreur côté backend
        showError(data.message || "Adresse e-mail ou mot de passe incorrect.");
        resetButton();
      }
    } catch (err) {
      console.error("Erreur JS :", err);
      showError("⚠️ Erreur serveur. Réessayez plus tard.");
      resetButton();
    }
  });

  // 🔁 Fonction pour réinitialiser l’état du bouton
  function resetButton() {
    if (btnText) btnText.style.display = "inline";
    if (btnSpinner) btnSpinner.style.display = "none";
    button.disabled = false;
  }

  function showError(text) {
    if (!errorBox) return;
    if (!text) {
      errorBox.style.display = "none";
      return;
    }
    errorBox.textContent = text;
    errorBox.style.display = "block";
    errorBox.classList.add("shake");
    setTimeout(() => errorBox.classList.remove("shake"), 600);
  }
});



// === Navigation vers "mot de passe oublié" ===
document.addEventListener('DOMContentLoaded', () => {
  const stepLogin = document.getElementById('modal-step-login');
  const stepResetEmail = document.getElementById('modal-step-reset-email');
  const stepResetNew = document.getElementById('modal-step-reset-new');
  const forgotLink = document.getElementById('js-forgot-password');
  const backToLogin1 = document.getElementById('js-back-to-login');
  const backToLogin2 = document.getElementById('js-back-to-login-from-reset');

  if (forgotLink && stepLogin && stepResetEmail) {
    forgotLink.addEventListener('click', (e) => {
      e.preventDefault();
      stepLogin.classList.add('fade-out');
      setTimeout(() => {
        stepLogin.style.display = 'none';
        stepLogin.classList.remove('fade-out');
        stepResetEmail.style.display = 'block';
        stepResetEmail.classList.add('fade-in');
        setTimeout(() => stepResetEmail.classList.remove('fade-in'), 400);
      }, 300);
    });
  }

  [backToLogin1, backToLogin2].forEach(btn => {
    if (!btn) return;
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      if (stepLogin && stepResetEmail && stepResetNew) {
        stepResetEmail.style.display = 'none';
        stepResetNew.style.display = 'none';
        stepLogin.style.display = 'block';
      }
    });
  });
});

// === Étape 1 : demande d'envoi de mail (reset) ===
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('resetRequestForm');
  if (!form) return;

  const btn = document.getElementById('resetRequestBtn');
  const errorBox = document.getElementById('resetError');
  if (!btn || !errorBox) return;

  const btnText = btn.querySelector('.btn-text');
  const spinner = btn.querySelector('.btn-spinner');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (btnText) btnText.style.display = 'none';
    if (spinner) spinner.style.display = 'inline-flex';
    btn.disabled = true;
    errorBox.style.display = 'none';

    const emailInput = form.querySelector('#resetEmail');
    const email = emailInput ? emailInput.value.trim() : '';

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ email })
      });
      const data = await res.json();

      if (data.success) {
        // Étape suivante (confirmation)
        errorBox.style.display = 'block';
        errorBox.textContent = "Un e-mail de réinitialisation vient d’être envoyé.";
        errorBox.classList.remove('error');
        errorBox.classList.add('success');
      } else {
        errorBox.style.display = 'block';
        errorBox.textContent = data.message || 'Erreur.';
        errorBox.classList.remove('success');
        errorBox.classList.add('error');
      }
    } catch {
      errorBox.style.display = 'block';
      errorBox.textContent = "⚠️ Erreur serveur. Réessayez plus tard.";
      errorBox.classList.remove('success');
      errorBox.classList.add('error');
    } finally {
      if (btnText) btnText.style.display = 'inline';
      if (spinner) spinner.style.display = 'none';
      btn.disabled = false;
    }
  });
});

// === Étape 2 : nouveau mot de passe (reset) ===
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('resetNewForm');
  if (!form) return;

  const btn = document.getElementById('resetNewBtn');
  const errorBox = document.getElementById('resetNewError');
  if (!btn || !errorBox) return;

  const btnText = btn.querySelector('.btn-text');
  const spinner = btn.querySelector('.btn-spinner');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const pass1Input = document.getElementById('newPassword');
    const pass2Input = document.getElementById('confirmPassword');
    const tokenInput = document.getElementById('resetToken');

    const pass1 = pass1Input ? pass1Input.value : '';
    const pass2 = pass2Input ? pass2Input.value : '';

    // Vérifications simples
    if (pass1 !== pass2) {
      errorBox.textContent = "Les mots de passe ne correspondent pas.";
      errorBox.classList.add('error');
      errorBox.style.display = 'block';
      return;
    }
    if (pass1.length < 12) {
      errorBox.textContent = "Le mot de passe doit contenir au moins 12 caractères.";
      errorBox.classList.add('error');
      errorBox.style.display = 'block';
      return;
    }

    if (btnText) btnText.style.display = 'none';
    if (spinner) spinner.style.display = 'inline-flex';
    btn.disabled = true;
    errorBox.style.display = 'none';

    const payload = {
      token: tokenInput ? tokenInput.value : '',
      password: pass1
    };

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();

      if (data.success) {
        // ✅ Pas de message affiché, transition fluide vers la connexion
        const resetNewStep = document.getElementById('modal-step-reset-new');
        const loginStep = document.getElementById('modal-step-login');

        if (resetNewStep && loginStep) {
          resetNewStep.classList.add('fade-out');
          setTimeout(() => {
            resetNewStep.style.display = 'none';
            resetNewStep.classList.remove('fade-out');
            loginStep.style.display = 'block';
            loginStep.classList.add('fade-in');
            setTimeout(() => loginStep.classList.remove('fade-in'), 400);
          }, 300);
        }
      } else {
        errorBox.textContent = data.message || 'Erreur.';
        errorBox.classList.add('error');
        errorBox.style.display = 'block';
      }
    } catch {
      errorBox.textContent = "⚠️ Erreur serveur. Réessayez plus tard.";
      errorBox.classList.add('error');
      errorBox.style.display = 'block';
    } finally {
      if (btnText) btnText.style.display = 'inline';
      if (spinner) spinner.style.display = 'none';
      btn.disabled = false;
    }
  });
});

// === Auto-ouverture du modal avec resetToken depuis l’URL ===
document.addEventListener("DOMContentLoaded", () => {
  const urlParams = new URLSearchParams(window.location.search);
  const token = urlParams.get("resetToken");
  if (!token) return;

  console.log("✅ Token détecté :", token);

  const modal = document.getElementById("registerModal");
  const tokenInput = document.getElementById("resetToken");

  // Injecte le token dans le champ caché
  if (tokenInput) tokenInput.value = token;

  // Liste de toutes les étapes du modal
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

  // Ouvre automatiquement la modale
  if (modal) {
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    document.documentElement.style.overflow = "hidden";
  }

  // Affiche uniquement la section "nouveau mot de passe"
  const stepResetNew = document.getElementById("modal-step-reset-new");
  if (stepResetNew) {
    stepResetNew.style.display = "block";
    stepResetNew.classList.add("fade-in");
    setTimeout(() => stepResetNew.classList.remove("fade-in"), 400);
  }
});

document.addEventListener("DOMContentLoaded", () => {
  const openBtn = document.getElementById("openPasswordRules");
  const closeBtn = document.getElementById("closePasswordRules");
  const modal = document.getElementById("passwordRulesModal");
  const passwordInput = document.getElementById("register-password");

  // ✅ Ouvre le panneau
  openBtn.addEventListener("click", (e) => {
    e.preventDefault();
    modal.classList.add("is-visible");
  });

  // ❌ Ferme le panneau
  closeBtn.addEventListener("click", (e) => {
    e.preventDefault();
    modal.classList.remove("is-visible");
  });

  // 🔎 Vérifie les critères en temps réel
  const rules = {
    length: document.getElementById("rule-length"),
    uppercase: document.getElementById("rule-uppercase"),
    lowercase: document.getElementById("rule-lowercase"),
    number: document.getElementById("rule-number"),
    special: document.getElementById("rule-special")
  };

  passwordInput.addEventListener("input", (e) => {
    const val = e.target.value;
    updateRule(rules.length, val.length >= 12);
    updateRule(rules.uppercase, /[A-Z]/.test(val));
    updateRule(rules.lowercase, /[a-z]/.test(val));
    updateRule(rules.number, /[0-9]/.test(val));
    updateRule(rules.special, /[^A-Za-z0-9]/.test(val));
  });

  function updateRule(element, valid) {
    element.classList.toggle("valid", valid);
  }
});
