<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>LINE 對話查詢 (linenewup)</title>
  <style>
    body{font-family:Microsoft JhengHei,Arial;margin:40px auto;max-width:900px;background:#f8f9fa}
    h1{color:#2c3e50;text-align:center}
    .form{text-align:center;margin:30px 0}
    input,button{padding:12px;font-size:16px;border-radius:6px;margin:5px}
    input{width:360px;border:1px solid #ddd}
    button{background:#3498db;color:white;border:none;cursor:pointer}
    button:hover{background:#2980b9}
    .export-btn{background:#27ae60;margin-left:10px}
    .export-btn:hover{background:#1e8449}
    table{width:100%;border-collapse:collapse;margin-top:20px;background:white;box-shadow:0 2px 10px #0001}
    th,td{padding:12px;border:1px solid #eee;text-align:left}
    th{background:#34495e;color:white}
    .status{padding:6px 12px;border-radius:20px;color:white}
    .ai{background:#27ae60}
    .human{background:#e74c3c}
    .unknown{background:#95a5a6}
    #result iframe{width:100%;height:800px;border:none}
    .search-box{width:300px !important;margin:10px auto;display:block}
  </style>
</head>
<body>
  <h1>LINE 對話查詢 (linenewup)</h1>
  <div class="form">
    <input id="user_id" placeholder="輸入 user_id" value="U0b19bcff720e94fa452b018800b385">
    <input id="pass" type="password" placeholder="密碼" value="08170513" style="width:150px">
    <button onclick="lookup()">查詢</button>
  </div>
  <div id="result"></div>

  <script>
    let allLogs = [];
    let currentIframe = null;

    async function lookup() {
      const userId = document.getElementById('user_id').value;
      const pass = document.getElementById('pass').value;
      const result = document.getElementById('result');
      
      if (!userId) return alert('請輸入 user_id');
      
      result.innerHTML = '<p>查詢中...</p>';
      
      try {
        const apiUrl = `https://epzzkliqpavsyonqenug.supabase.co/functions/v1/chat-lookup?user_id=${userId}&pass=${pass}`;
        const res = await fetch(apiUrl);
        if (!res.ok) throw new Error('密碼錯誤或無權限');
        
        const html = await res.text();
        
        // 解析 HTML 取得資料
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        const statusEl = doc.querySelector('.status');
        const status = statusEl ? (statusEl.classList.contains('ai') ? 'ai' : statusEl.classList.contains('human') ? 'human' : 'unknown') : 'unknown';
        const statusText = status === 'ai' ? 'AI 模式' : status === 'human' ? '真人模式' : '未知';
        
        const rows = doc.querySelectorAll('table tr:not(:first-child)');
        allLogs = Array.from(rows).map(row => {
          const tds = row.querySelectorAll('td');
          return {
            time: tds[0]?.innerText.trim() || '',
            user: tds[1]?.innerHTML.replace(/<br>/g, '\n') || '',
            bot: tds[2]?.innerHTML.replace(/<br>/g, '\n') || ''
          };
        });

        // 顯示搜尋框 + 結果
        result.innerHTML = `
          <p style="text-align:center"><b>狀態：</b>
            <span class="status ${status}">${statusText}</span>
          </p>
          <input type="text" id="search" class="search-box" placeholder="輸入關鍵字搜尋（如：訂單）" onkeyup="filterLogs()">
          <div id="table-container"></div>
        `;
        
        renderTable(allLogs);
        
      } catch (err) {
        result.innerHTML = `<p style="color:red;text-align:center">錯誤：${err.message}</p>`;
      }
    }

    function filterLogs() {
      const keyword = document.getElementById('search').value.toLowerCase();
      const filtered = allLogs.filter(log => 
        log.user.toLowerCase().includes(keyword) || 
        log.bot.toLowerCase().includes(keyword)
      );
      renderTable(filtered);
    }

    function renderTable(logs) {
      const container = document.getElementById('table-container');
      if (logs.length === 0) {
        container.innerHTML = `<p style="text-align:center;color:#7f8c8d">無符合條件的對話</p>`;
        return;
      }

      // 匯出 Excel
      const csv = [
        ["時間", "用戶說", "Bot 回應"],
        ...logs.map(l => [l.time, l.user.replace(/\n/g, ' '), l.bot.replace(/\n/g, ' ')])
      ].map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
      
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);

      let tableHtml = `
        <button class="export-btn" onclick="window.location.href='${url}'" download="搜尋結果.csv">匯出 Excel</button>
        <h2>共 ${logs.length} 筆對話</h2>
        <table>
          <tr><th>時間</th><th>用戶說</th><th>Bot 回應</th></tr>
      `;

      logs.forEach(l => {
        const u = l.user.replace(/\n/g, '<br>');
        const b = l.bot.replace(/\n/g, '<br>');
        tableHtml += `<tr><td>${l.time}</td><td>${u}</td><td>${b}</td></tr>`;
      });

      tableHtml += `</table>`;
      container.innerHTML = tableHtml;
    }
  </script>
</body>
</html>
