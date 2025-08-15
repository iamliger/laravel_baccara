document.addEventListener('livewire:load', function () {
    let isInitialized = false;

    Livewire.hook('message.processed', (message, component) => {
        if (isInitialized) return;

        console.log('Baccarat Script Initialized!');

        const baccaraContainer = document.querySelector('.baccara-container');
        if (!baccaraContainer) return;
        
        const initialData = JSON.parse(baccaraContainer.dataset.initial || '{}');
        let jokboHistory = initialData.jokboHistory || "";

        const el = {
            playerBtn: document.getElementById('player-btn'),
            bankerBtn: document.getElementById('banker-btn'),
            undoBtn: document.getElementById('undo-btn'),
            resetBtn: document.getElementById('reset-btn'),
            playerCountSpan: document.getElementById('player-count'),
            bankerCountSpan: document.getElementById('banker-count'),
            totalCountSpan: document.getElementById('total-count'),
            mainRoadmapGrid: document.getElementById('roadmap-grid'),
            mainRoadmapContainer: document.getElementById('main-roadmap-container'),
            historyBoxes: {
                3: { box: document.getElementById('history-box-3'), grid: document.getElementById('history-grid-3') },
                4: { box: document.getElementById('history-box-4'), grid: document.getElementById('history-grid-4') },
                5: { box: document.getElementById('history-box-5'), grid: document.getElementById('history-grid-5') },
                6: { box: document.getElementById('history-box-6'), grid: document.getElementById('history-grid-6') },
            },
            logicBtnGroup: document.getElementById('logic-btn-group'),
            logicButtons: document.querySelectorAll('.logic-btn'),
            viewToggleButton: document.getElementById('view-toggle-btn'),
            consoleWrapper: document.getElementById('console-wrapper'),
            chartWrapper: document.getElementById('chart-wrapper'),
            consoleBox: document.getElementById('console'),
        };

        let mainRoadmapOccupied = {};
        let mainRoadmapLastType = null;
        let mainRoadmapCurrentCol = 1;
        let mainRoadmapLastPlacedPos = null;
        let historyPlacement = {};
        let actionHistory = [];

        if (el.playerBtn) el.playerBtn.addEventListener('click', () => addHistoryItem('player'));
        if (el.bankerBtn) el.bankerBtn.addEventListener('click', () => addHistoryItem('banker'));
        if (el.undoBtn) el.undoBtn.addEventListener('click', () => undoLastAction());
        if (el.resetBtn) el.resetBtn.addEventListener('click', () => resetHistory());
        
        function checkCellEmpty(col, row) {
            const MAX_ROW = 6;
            if (col <= 0 || row <= 0 || row > MAX_ROW) return false;
            return !mainRoadmapOccupied[`c${col}-r${row}`];
        }

        function calculateMainRoadmapPosition(type) {
            const MAX_ROW = 6;
            let targetCol, targetRow;
            let mainRoadmapLastRow = mainRoadmapLastPlacedPos ? mainRoadmapLastPlacedPos.row : 0;
            if (mainRoadmapLastType === null) {
                targetCol = 1; targetRow = 1;
            } else if (type !== mainRoadmapLastType) {
                if (mainRoadmapLastPlacedPos && mainRoadmapLastPlacedPos.row === 1 && mainRoadmapLastPlacedPos.col !== mainRoadmapCurrentCol) {
                    targetCol = mainRoadmapLastPlacedPos.col + 1;
                } else {
                    targetCol = mainRoadmapCurrentCol + 1;
                }
                targetRow = 1;
            } else {
                targetCol = mainRoadmapCurrentCol;
                targetRow = mainRoadmapLastRow + 1;
                if (targetRow > MAX_ROW || !checkCellEmpty(targetCol, targetRow)) {
                    if (!mainRoadmapLastPlacedPos) return null;
                    targetRow = mainRoadmapLastPlacedPos.row;
                    targetCol = mainRoadmapLastPlacedPos.col + 1;
                    while (!checkCellEmpty(targetCol, targetRow)) {
                        targetCol++;
                        if (targetCol > 1000) return null;
                    }
                }
            }
            return { col: targetCol, row: targetRow };
        }

        function addGridItem(gridId, row, col, type, isHistory = false) {
            const grid = document.getElementById(gridId);
            if (!grid) return null;
            const node = document.createElement('div');
            const circleClass = type === 'player' ? 'grid-item-player' : 'grid-item-banker';
            const itemId = `item-${Date.now()}-${Math.random().toString(36).substring(2, 7)}`;
            node.id = itemId;
            if (isHistory) {
                node.className = `history-item-wrapper grid-item-circle ${circleClass}`;
                node.textContent = type === 'player' ? 'P' : 'B';
            } else {
                node.className = `grid-item-wrapper`;
                node.innerHTML = `<div class="grid-item-circle ${circleClass}">${type === 'player' ? 'P' : 'B'}</div>`;
            }
            node.style.gridColumn = col;
            node.style.gridRow = row;
            grid.appendChild(node);
            return itemId;
        }

        function updateCounts() {
            const pCount = (jokboHistory.match(/P/g) || []).length;
            const bCount = (jokboHistory.match(/B/g) || []).length;
            el.playerCountSpan.textContent = pCount;
            el.bankerCountSpan.textContent = bCount;
            el.totalCountSpan.textContent = pCount + bCount;
        }

        function redrawAllFromJokbo() {
            mainRoadmapOccupied = {};
            mainRoadmapLastType = null;
            mainRoadmapCurrentCol = 1;
            mainRoadmapLastPlacedPos = null;
            historyPlacement = {};
            actionHistory = [];
            el.roadmapGrid.innerHTML = '';
            Object.values(el.historyBoxes).forEach(h => { if (h && h.grid) h.grid.innerHTML = ''; });
            const arr = jokboHistory.split('').filter(c => c === 'P' || c === 'B');
            for (const char of arr) {
                const type = char === 'P' ? 'player' : 'banker';
                const currentActionItems = [];
                const position = calculateMainRoadmapPosition(type);
                if (position) {
                    const itemId = addGridItem('roadmap-grid', position.row, position.col, type, false);
                    if (itemId) {
                        currentActionItems.push({ gridId: 'roadmap-grid', row: position.row, col: position.col, type, itemId });
                        mainRoadmapOccupied[`c${position.col}-r${position.row}`] = true;
                        if (mainRoadmapLastType === null || type !== mainRoadmapLastType) {
                            mainRoadmapCurrentCol = position.col;
                        }
                        mainRoadmapLastType = type;
                        mainRoadmapLastPlacedPos = { col: position.col, row: position.row };
                    }
                }
                for (let i = 3; i <= 6; i++) {
                    const gridId = `history-grid-${i}`;
                    if (!historyPlacement[gridId]) historyPlacement[gridId] = { col: 1, row: 1 };
                    let { col, row } = historyPlacement[gridId];
                    const maxRows = i;
                    const itemId = addGridItem(gridId, row, col, type, true);
                    if (itemId) {
                        currentActionItems.push({ gridId, row, col, type, itemId });
                        row++;
                        if (row > maxRows) { row = 1; col++; }
                        historyPlacement[gridId] = { col, row };
                    }
                }
                actionHistory.push(currentActionItems);
            }
            updateCounts();
            setTimeout(() => {
                if (el.mainRoadmapContainer) { el.mainRoadmapContainer.scrollLeft = el.mainRoadmapContainer.scrollWidth; }
                Object.values(el.historyBoxes).forEach(hb => { if (hb && hb.box) { hb.box.scrollLeft = hb.box.scrollWidth; } });
            }, 0);
        }
        
        function addHistoryItem(type) {
            jokboHistory += (type === 'player' ? 'P' : 'B');
            redrawAllFromJokbo();
            addConsoleMessage(`${type === 'player' ? '플레이어' : '뱅커'}를 선택했습니다.`, type);
            if (window.Livewire) Livewire.emit('addResult', type === 'player' ? 'P' : 'B');
        }

        function undoLastAction() {
            if (jokboHistory.length === 0) return;
            jokboHistory = jokboHistory.slice(0, -1);
            redrawAllFromJokbo();
            addConsoleMessage('마지막 입력을 취소했습니다.', 'system');
            if (window.Livewire) Livewire.emit('undo');
        }

        function resetHistory() {
            if (confirm('정말로 모든 기록을 초기화하시겠습니까?')) {
                jokboHistory = '';
                redrawAllFromJokbo();
                clearConsole();
                addConsoleMessage('게임을 리셋하였습니다.', 'system');
                if (window.Livewire) Livewire.emit('resetGame');
            }
        }

        function addConsoleMessage(message, type = 'info') {
            if (!el.consoleBox) return;
            const div = document.createElement('div');
            div.textContent = message;
            div.className = `console-message type-${type}`;
            el.consoleBox.appendChild(div);
            el.consoleBox.scrollTop = el.consoleBox.scrollHeight;
        }
        function clearConsole() { if (el.consoleBox) el.consoleBox.innerHTML = ''; }
        function updateLogicButtonsUI(selectedValue) {
            el.logicButtons.forEach(btn => {
                const on = btn.dataset.value === selectedValue;
                btn.setAttribute('aria-checked', on);
                btn.classList.toggle('bg-indigo-600', on); btn.classList.toggle('text-white', on);
                btn.classList.toggle('bg-gray-700', !on); btn.classList.toggle('text-gray-200', !on);
            });
        }
        if (el.logicBtnGroup) {
            el.logicBtnGroup.addEventListener('click', (e) => {
                const btn = e.target.closest('.logic-btn');
                if (!btn) return;
                const v = btn.dataset.value;
                updateLogicButtonsUI(v);
                localStorage.setItem('selectedLogic', v);
                addConsoleMessage(`'${v}' 로직으로 변경되었습니다.`, 'system');
            });
        }
        if (el.viewToggleButton) {
            el.viewToggleButton.addEventListener('click', () => {
                const cur = el.viewToggleButton.dataset.currentView;
                if (cur === 'console') {
                    el.consoleWrapper.classList.add('hidden');
                    el.chartWrapper.classList.remove('hidden');
                    el.viewToggleButton.textContent = '콘솔 보기';
                    el.viewToggleButton.dataset.currentView = 'chart';
                } else {
                    el.chartWrapper.classList.add('hidden');
                    el.consoleWrapper.classList.remove('hidden');
                    el.viewToggleButton.textContent = '차트 보기';
                    el.viewToggleButton.dataset.currentView = 'console';
                }
            });
        }
        
        (function init() {
            console.log('First Start');
            const savedLogic = localStorage.getItem('selectedLogic') || 'logic1';
            updateLogicButtonsUI(savedLogic);
            addConsoleMessage('바카라 분석 시스템에 오신 것을 환영합니다.', 'system');
            redrawAllFromJokbo();
        })();

        isInitialized = true;
    });
});