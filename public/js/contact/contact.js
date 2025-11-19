document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector(".form-area form");
  const btn = form.querySelector(".btn-modern");
  const spinner = btn.querySelector(".spinner");

  form.addEventListener("submit", () => {
    btn.classList.add("loading");
    spinner.style.display = "block";
    btn.disabled = true;
  });

  const flashMessages = document.querySelectorAll(".flash-success");
  flashMessages.forEach((msg) => {
    setTimeout(() => {
      msg.style.transition = "opacity 0.5s ease";
      msg.style.opacity = "0";
      setTimeout(() => msg.remove(), 500);
    }, 3000);
  });
});
