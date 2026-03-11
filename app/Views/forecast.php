<?php $pageTitle = 'ARIMA Forecasting'; ?>

<div class="bg-white p-6 rounded shadow">
  <div class="text-sm text-slate-500">ARIMA Demo</div>
  <div class="text-lg font-semibold">Forecast Runner</div>

  <form class="mt-4 space-y-3" method="post" action="/forecast/run">
    <div>
      <label class="block text-sm text-slate-600">Series Key</label>
      <input name="series_key" value="visits_total" class="mt-1 w-full border rounded px-3 py-2" />
    </div>
    <div>
      <label class="block text-sm text-slate-600">Horizon (days)</label>
      <input name="horizon" value="30" type="number" class="mt-1 w-full border rounded px-3 py-2" />
    </div>
    <button class="bg-slate-900 text-white px-4 py-2 rounded" type="submit">Run Forecast</button>
  </form>

  <p class="mt-3 text-sm text-slate-500">The forecast response is JSON. You can wire this to a chart next.</p>
</div>
