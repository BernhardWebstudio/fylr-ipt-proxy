import { startStimulusApp } from '@symfony/stimulus-bundle';
import StatsChartController from './controllers/stats_chart_controller.js';
import BootstrapDropdownController from './controllers/bootstrap_dropdown_controller.js';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
app.register('stats-chart', StatsChartController);
app.register('bootstrap-dropdown', BootstrapDropdownController);
