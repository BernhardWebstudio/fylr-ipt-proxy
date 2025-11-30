import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto';

export default class extends Controller {
  static values = {
    byType: Array,
    byTag: Array,
  };

  connect() {
    const ctx = this.element.getContext('2d');

    // Use translation keys for unknown/none
    const translator = window.SymfonyTranslator || ((key) => key); // fallback if not loaded
    const typeLabels = (this.byTypeValue || []).map(r => r.objectType ?? translator('label.unknown'));
    const typeCounts = (this.byTypeValue || []).map(r => r.count);

    const tagLabels = (this.byTagValue || []).map(r => (r.tagId ?? translator('label.none')));
    const tagCounts = (this.byTagValue || []).map(r => r.count);

    // Build unified labels as the union of type labels and tag labels (with prefixes for clarity)
    const prefixedTypeLabels = typeLabels.map(l => `${translator('label.type')}: ${l}`);
    const prefixedTagLabels = tagLabels.map(l => `${translator('label.tag')}: ${l}`);
    const labelSet = new Set([...prefixedTypeLabels, ...prefixedTagLabels]);
    const allLabels = Array.from(labelSet);

    // Map label -> count for each dataset, filling missing with 0
    const typeMap = new Map(prefixedTypeLabels.map((l, i) => [l, typeCounts[i]]));
    const tagMap = new Map(prefixedTagLabels.map((l, i) => [l, tagCounts[i]]));

    const typeDataUnified = allLabels.map(l => typeMap.get(l) ?? 0);
    const tagDataUnified = allLabels.map(l => tagMap.get(l) ?? 0);

    const datasets = [];
    if (prefixedTypeLabels.length) {
      datasets.push({
        label: translator('chart.by_object_type'),
        data: typeDataUnified,
        backgroundColor: 'rgba(54, 162, 235, 0.5)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1,
      });
    }
    if (prefixedTagLabels.length) {
      datasets.push({
        label: translator('chart.by_tag_id'),
        data: tagDataUnified,
        backgroundColor: 'rgba(255, 159, 64, 0.5)',
        borderColor: 'rgba(255, 159, 64, 1)',
        borderWidth: 1,
      });
    }

    this.chart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: allLabels,
        datasets,
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: { precision: 0 }
          }
        },
        plugins: {
          legend: { position: 'bottom' },
          title: { display: true, text: translator('chart.specimen_overview') }
        }
      }
    });
  }

  disconnect() {
    if (this.chart) {
      this.chart.destroy();
    }
  }
}
