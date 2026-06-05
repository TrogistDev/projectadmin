// components/SummaryCards.js
const SummaryCards = {
  render(projects, helpers = Helpers) {
    const count = projects.reduce((acc, project) => {
      acc[project.estado] = (acc[project.estado] || 0) + 1;
      return acc;
    }, {});

    const labels = [
      { key: "planificacion", title: "Planejamento", color: "secondary" },
      { key: "en_curso", title: "Em curso", color: "info" },
      { key: "pausado", title: "Pausado", color: "warning" },
      { key: "finalizado", title: "Finalizado", color: "success" }
    ];

    return labels.map(item => `
      <div class="col-md-3 mb-2">
        <div class="card text-white bg-${item.color} h-100">
          <div class="card-body">
            <h6>${item.title}</h6>
            <h3>${count[item.key] || 0}</h3>
          </div>
        </div>
      </div>
    `).join("");
  }
};
