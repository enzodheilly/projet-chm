					document.addEventListener('DOMContentLoaded', () => {
																					  const form = document.querySelector('.filter-bar form');
																					  if (!form) return;
																					
																					  // Soumission automatique sur changement de filtre
																					  form.querySelectorAll('select[name="categorie"], input[type="date"]').forEach(el => {
																					    el.addEventListener('change', () => form.submit());
																					  });
																					
																					  // Réinitialisation rapide des dates
																					  window.clearDate = function(id) {
																					    const input = document.getElementById(id);
																					    if (input) {
																					      input.value = '';
																					      form.submit();
																					    }
																					  };
																					});