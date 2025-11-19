																				document.addEventListener("DOMContentLoaded", () => {
																				  // === GESTION DU MENU LATÉRAL ===
																				  document.querySelectorAll(".faq-sidebar li").forEach(li => {
																				    li.addEventListener("click", e => {
																				      e.preventDefault();
																				
																				      // Retirer "active" des autres liens
																				      document.querySelectorAll(".faq-sidebar li").forEach(item => item.classList.remove("active"));
																				      li.classList.add("active");
																				
																				      // Récupérer la section ciblée
																				      const target = li.getAttribute("data-section");
																				
																				      // Masquer toutes les sections
																				      document.querySelectorAll(".faq-content").forEach(content => {
																				        content.style.display = "none";
																				      });
																				
																				      // Afficher la section correspondante
																				      const activeContent = document.querySelector(`.faq-content[data-section="${target}"]`);
																				      if (activeContent) activeContent.style.display = "block";
																				    });
																				  });
																				
																				  // === GESTION DES QUESTIONS / RÉPONSES ===
																				  document.querySelectorAll(".faq-question").forEach(question => {
																				    question.addEventListener("click", () => {
																				      const item = question.closest(".faq-item");
																				      const answer = item.querySelector(".faq-answer");
																				      const span = question.querySelector("span");
																				
																				      // Fermer les autres questions si souhaité (optionnel)
																				      // document.querySelectorAll(".faq-item").forEach(i => {
																				      //   if (i !== item) {
																				      //     i.classList.remove("active");
																				      //     const a = i.querySelector(".faq-answer");
																				      //     const s = i.querySelector(".faq-question span");
																				      //     if (a) a.style.maxHeight = null;
																				      //     if (s) s.textContent = "+";
																				      //   }
																				      // });
																				
																				      // Toggle de l’état actif
																				      item.classList.toggle("active");
																				
																				      // Animation ouverture/fermeture
																				      if (item.classList.contains("active")) {
																				        answer.style.maxHeight = answer.scrollHeight + "px";
																				        answer.style.opacity = "1";
																				        span.textContent = "−";
																				        span.style.transform = "rotate(180deg)";
																				        span.style.color = "#005b94";
																				      } else {
																				        answer.style.maxHeight = null;
																				        answer.style.opacity = "0";
																				        span.textContent = "+";
																				        span.style.transform = "rotate(0deg)";
																				        span.style.color = "#005b94";
																				      }
																				    });
																				  });
																				});