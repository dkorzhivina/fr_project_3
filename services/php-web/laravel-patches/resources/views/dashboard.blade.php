{{-- services/php-web/resources/views/layouts/dashboard.blade.php --}}
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <style>
    table { width:100%; border-collapse: collapse; }
    th, td { padding:8px; border-bottom:1px solid #eee; text-align:left; }
    th.sortable { cursor:pointer; }
    .highlight { background:#f9f9f9; }
    .fade-enter { animation: fadeIn .35s; }
    @keyframes fadeIn { from {opacity:0; transform: translateY(6px);} to {opacity:1; transform:none;} }
  </style>
</head>
<body class="p-4">

<div x-data="dashboard()" class="animate__animated animate__fadeIn">
  <h1>Dashboard</h1>

  <div style="display:flex;gap:10px;margin-bottom:12px;align-items:center">
    <input x-model="q" placeholder="Поиск..." @keydown.enter="fetch()" />
    <select x-model="sort_by">
      <option value="created_at">Дата</option>
      <option value="value">Значение</option>
      <option value="id">ID</option>
    </select>
    <select x-model="order">
      <option value="desc">По убыванию</option>
      <option value="asc">По возрастанию</option>
    </select>
    <input id="date_from" placeholder="От" />
    <input id="date_to" placeholder="До" />
    <button @click="fetch()">Фильтровать</button>
    <button @click="reset()">Сброс</button>
  </div>

  <div style="display:flex;gap:20px;">
    <div style="flex:1;">
      <table>
        <thead>
          <tr>
            <th class="sortable" @click="toggleSort('id')">ID</th>
            <th class="sortable" @click="toggleSort('created_at')">Дата</th>
            <th class="sortable" @click="toggleSort('value')">Значение</th>
          </tr>
        </thead>
        <tbody>
          <template x-for="row in rows" :key="row.id">
            <tr class="fade-enter">
              <td x-text="row.id"></td>
              <td x-text="new Date(row.created_at).toLocaleString()"></td>
              <td x-text="row.value"></td>
            </tr>
          </template>
        </tbody>
      </table>
      <div style="margin-top:10px;">
        <button :disabled="page==1" @click="page--; fetch()">Prev</button>
        <span x-text="page"></span>
        <button @click="page++; fetch()">Next</button>
      </div>
    </div>

    <div style="width:400px;">
      <canvas id="trendChart"></canvas>
    </div>
  </div>
</div>

<script>
function dashboard() {
  return {
    q: '',
    sort_by: 'created_at',
    order: 'desc',
    page: 1,
    per_page: 25,
    rows: [],
    chart: null,

    init() {
      flatpickr('#date_from', { onChange: (d) => this.date_from = d[0] ? d[0].toISOString().slice(0,10) : ''});
      flatpickr('#date_to',   { onChange: (d) => this.date_to   = d[0] ? d[0].toISOString().slice(0,10) : ''});
      this.fetch();
    },

    toggleSort(col) {
      if (this.sort_by === col) {
        this.order = this.order === 'asc' ? 'desc' : 'asc';
      } else {
        this.sort_by = col;
        this.order = 'asc';
      }
      this.fetch();
    },

    async fetch() {
      const params = new URLSearchParams({
        q: this.q,
        sort_by: this.sort_by,
        order: this.order,
        page: this.page,
        per_page: this.per_page,
        date_from: this.date_from || '',
        date_to: this.date_to || ''
      });
      const res = await fetch(`/dashboard/data?${params.toString()}`, {headers:{'X-Requested-With':'XMLHttpRequest'}});
      const json = await res.json();
      this.rows = json.data || json; 
      this.renderChart(json);
    },

    renderChart(data) {
      const labels = (data.data || data).map(r => new Date(r.created_at).toLocaleTimeString());
      const values = (data.data || data).map(r => r.value || 0);
      if (!this.chart) {
        const ctx = document.getElementById('trendChart').getContext('2d');
        this.chart = new Chart(ctx, {
          type: 'line',
          data: { labels, datasets: [{ label: 'Trend', data: values, fill:false }]},
          options: { responsive:true }
        });
      } else {
        this.chart.data.labels = labels;
        this.chart.data.datasets[0].data = values;
        this.chart.update();
      }
    },

    reset() {
      this.q = '';
      this.sort_by = 'created_at';
      this.order = 'desc';
      this.page = 1;
      this.date_from = '';
      this.date_to = '';
      document.getElementById('date_from').value = '';
      document.getElementById('date_to').value = '';
      this.fetch();
    }
  }
}
</script>

</body>
</html>
