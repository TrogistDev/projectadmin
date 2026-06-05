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

  calculateProgress(projects) {
    // projects = array de projetos (já carregados)
    return projects.reduce((acc, project) => {
      const totalPhases = project.phases?.length || 0;
      const completedPhases = project.phases?.filter(p => p.completada).length || 0;
      acc[project.id] = totalPhases > 0 ? Math.round((completedPhases / totalPhases) * 100) : 0;
      return acc;
    }, {});
  },

  calculateProjectStatus(phases, currentEstado) {
    if (!Array.isArray(phases)) return "planificacion";

    const completed = phases.filter(p => p.completada).length;
    const total = phases.length;

    if (currentEstado === "pausado") return "pausado";
    if (total === 0 || completed === 0) return "planificacion";
    if (completed < total) return "en_curso";
    if (completed === total) return "finalizado";

    return "planificacion"; // fallback
  },

  // Helper para ordenação cronológica de projetos
  sortProjectsByDate(projects, field = "fecha_inicio") {
    return [...projects].sort((a, b) => {
      const dateA = new Date(a[field] + "T00:00:00");
      const dateB = new Date(b[field] + "T00:00:00");
      return dateA - dateB;
    });
  }
};
