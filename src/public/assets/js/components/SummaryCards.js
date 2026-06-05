// components/SummaryCards.js
const SummaryCards = {
  render(responseData) {
    const counts = responseData?.totais_por_estado || {
      planificacion: 0,
      en_curso: 0,
      pausado: 0,
      finalizado: 0,
    };

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
            <h3>${counts[item.key] || 0}</h3>
          </div>
        </div>
      </div>
    `).join("");
  }
};
