// utils/helpers.js
const Helpers = {
  formatDate(dateStr) {
    if (!dateStr) return "-";
    const date = new Date(dateStr + "T00:00:00");
    return isNaN(date.getTime()) ? "-" : date.toLocaleDateString("pt-BR");
  },

  statusLabel(status) {
    switch (status) {
      case "planificacion": return "Planejamento";
      case "en_curso": return "Em curso";
      case "pausado": return "Pausado";
      case "finalizado": return "Finalizado";
      default: return status;
    }
  },

  renderResponsibleOptions(users, selectedId = null) {
    const validManagers = users.filter(
      (u) => u.rol === "jefe_proyecto" || u.rol === "administrador"
    );
    const options = validManagers
      .map((u) => `<option value="${u.id}" ${u.id == selectedId ? 'selected' : ''}>${u.nombre} ${u.apellidos} (${u.rol === "administrador" ? "Admin" : "Jefe"})</option>`)
      .join("");
    return '<option value="">Selecione...</option>' + options;
  }
};
