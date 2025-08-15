@php
    $initialData = json_encode([
        'jokboHistory' => $jokboHistory,
        'moneyArrStep' => $moneyArrStep,
    ]);
@endphp

<div class="baccara-container" data-initial="{{ $initialData }}">
    <header id="prediction-header" class="prediction-header"></header>
    <main class="baccara-main-content">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
            <div class="flex flex-wrap items-center gap-2">
                <button id="player-btn"
                    class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm shadow">플레이어 <span
                        id="player-count">{{ $playerCount }}</span></button>
                <button id="banker-btn"
                    class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm shadow">뱅커 <span
                        id="banker-count">{{ $bankerCount }}</span></button>
            </div>
            <div class="flex items-center gap-2">
                <div id="logic-btn-group" role="radiogroup" class="logic-btn-group">
                    <button type="button" role="radio" aria-checked="true" data-value="logic1"
                        class="logic-btn">로직1</button>
                    <button type="button" role="radio" aria-checked="false" data-value="logic2"
                        class="logic-btn">로직2</button>
                    <button type="button" role="radio" aria-checked="false" data-value="logic3"
                        class="logic-btn">로직3</button>
                    <button type="button" role="radio" aria-checked="false" data-value="logic4"
                        class="logic-btn">로직4</button>
                </div>
                <span class="px-3 py-1.5 bg-gray-600 text-white rounded-md text-sm shadow">합: <span id="total-count"
                        class="font-semibold ml-1">{{ $totalCount }}</span></span>
                <button id="undo-btn"
                    class="px-4 py-1.5 bg-gray-500 hover:bg-gray-600 text-black rounded-md text-sm shadow">취소</button>
            </div>
        </div>
        <div id="main-roadmap-container"
            class="relative border border-gray-700 rounded-md overflow-x-auto mb-4 shadow-inner checkerboard-bg main-grid-bg"
            style="height: calc(var(--main-cell-size) * 6);">
            <div id="roadmap-grid"></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            @for ($i = 3; $i <= 6; $i++)
                <div id="history-box-{{ $i }}" data-rows="{{ $i }}"
                    class="relative border border-gray-700 rounded-md shadow checkerboard-bg history-grid-bg overflow-x-auto"
                    style="height: calc(var(--history-cell-size) * {{ $i }});">
                    <div id="history-grid-{{ $i }}"></div>
                </div>
            @endfor
        </div>
        <div id="interactive-area" class="relative console-box-container">
            <button id="copy-log-btn" type="button">로그 복사</button>
            <div id="console-wrapper" class="toggle-view">
                <div class="console-box" id="console"></div>
            </div>
            <div id="chart-wrapper" class="toggle-view hidden">
                <div class="console-box"><canvas id="analytics-chart-inline"></canvas></div>
            </div>
            <button id="view-toggle-btn" data-current-view="console"
                class="absolute px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-md shadow-lg z-10">차트
                보기</button>
        </div>
        <div class="flex flex-wrap justify-between items-center gap-2 mt-4">
            <button id="reset-btn"
                class="px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm shadow">리셋</button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();"
                    class="px-4 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-sm shadow inline-block">로그아웃</a>
            </form>
        </div>

        <div id="moneystepinfo-modal" class="modal-overlay hidden">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 class="modal-title">게임 설정</h3>
                </div>
                <div class="modal-body">
                    <form id="moneystepinfo-form" onsubmit="return false;">
                        <div class="mb-4">
                            <label for="money" class="setting-label mb-2 block">시작금액:</label>
                            <input type="number" id="money" inputmode="numeric" class="modal-input"
                                placeholder="예: 1000">
                        </div>
                    </form>
                </div>
                <div class="modal-footer"><button type="button" id="set-money-btn"
                        class="modal-btn modal-btn-primary w-full">확인</button></div>
            </div>
        </div>
        <div id="setting-box-modal" class="modal-overlay hidden">
            <div class="modal-box">
                <div class="modal-header">
                    <h3 class="modal-title">환경설정</h3><button type="button" class="modal-close-btn"
                        data-modal-id="setting-box-modal"><i class="ti ti-x text-xl"></i></button>
                </div>
                <div class="modal-body">
                    <form id="setting-box-form" class="space-y-6">
                        <div class="setting-item"><label for="gamesetting"
                                class="setting-label">게임셋팅하기</label><button type="button" role="switch"
                                aria-checked="false" id="gamesetting" class="setting-toggle"><span
                                    class="setting-toggle-knob"></span></button></div>
                        <div class="setting-item"><label for="soundsetting" class="setting-label">소리설정</label><button
                                type="button" role="switch" aria-checked="false" id="soundsetting"
                                class="setting-toggle"><span class="setting-toggle-knob"></span></button></div>
                        <div class="setting-item"><label for="chkmoneyinfo"
                                class="setting-label">금액정보보이기</label><button type="button" role="switch"
                                aria-checked="false" id="chkmoneyinfo" class="setting-toggle"><span
                                    class="setting-toggle-knob"></span></button></div>
                        <div class="setting-item"><label for="chkconsole"
                                class="setting-label">콘솔박스보이기</label><button type="button" role="switch"
                                aria-checked="true" id="chkconsole" class="setting-toggle"><span
                                    class="setting-toggle-knob"></span></button></div>
                        <div class="setting-item"><label for="chkvirtualbet" class="setting-label">가상배팅
                                활성화</label><button type="button" role="switch" aria-checked="true"
                                id="chkvirtualbet" class="setting-toggle"><span
                                    class="setting-toggle-knob"></span></button></div>
                    </form>
                </div>
                <div class="modal-footer"><button type="button"
                        class="modal-btn modal-btn-secondary modal-close-btn"
                        data-modal-id="setting-box-modal">닫기</button><button type="button"
                        class="modal-btn modal-btn-primary modal-close-btn"
                        data-modal-id="setting-box-modal">확인</button></div>
            </div>
        </div>
    </main>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:load', function() {
            // -----------------------------
            // 상태/요소 참조
            // -----------------------------
            const baccaraContainer = document.querySelector('.baccara-container');
            if (!baccaraContainer) return;

            const initialData = JSON.parse(baccaraContainer.dataset.initial || '{}');
            let jokboHistory, moneyArrStep, consoleMessages, currentSettings;

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
                    3: {
                        box: document.getElementById('history-box-3'),
                        grid: document.getElementById('history-grid-3')
                    },
                    4: {
                        box: document.getElementById('history-box-4'),
                        grid: document.getElementById('history-grid-4')
                    },
                    5: {
                        box: document.getElementById('history-box-5'),
                        grid: document.getElementById('history-grid-5')
                    },
                    6: {
                        box: document.getElementById('history-box-6'),
                        grid: document.getElementById('history-grid-6')
                    },
                },
                logicBtnGroup: document.getElementById('logic-btn-group'),
                logicButtons: document.querySelectorAll('.logic-btn'),
                consoleBox: document.getElementById('console'),
                viewToggleButton: document.getElementById('view-toggle-btn'),
                consoleWrapper: document.getElementById('console-wrapper'),
                chartWrapper: document.getElementById('chart-wrapper'),
                predictionHeader: document.getElementById('prediction-header'),
                interactiveArea: document.getElementById('interactive-area'),
                copyLogBtn: document.getElementById('copy-log-btn'),
                settingsModal: document.getElementById('setting-box-modal'),
                settingsCloseBtns: document.querySelectorAll('.modal-close-btn'),
                settingsToggles: document.querySelectorAll('.setting-toggle'),
                moneyModal: document.getElementById('moneystepinfo-modal'),
                setMoneyBtn: document.getElementById('set-money-btn'),
                moneyInput: document.getElementById('money'),
            };

            const STORAGE_KEY = `baccara_state_{{ auth()->user()->name }}`;
            const CONSOLE_STORAGE_KEY = `baccara_console_{{ auth()->user()->name }}`;
            const SETTINGS_KEY = `baccara_settings_{{ auth()->user()->name }}`;

            // -----------------------------
            // Livewire 이벤트 수신
            // -----------------------------
            Livewire.on('jokboUpdated', (newJokbo, counts, newMoneyArrStep) => {
                jokboHistory = newJokbo || '';
                moneyArrStep = newMoneyArrStep || [];

                redrawAllFromJokbo(); // 오직 로드맵만 다시 그림
                saveState(); // 족보 상태 저장

                if (counts) {
                    el.playerCountSpan.textContent = counts.player;
                    el.bankerCountSpan.textContent = counts.banker;
                    el.totalCountSpan.textContent = counts.total;
                }
            });

            Livewire.on('predictionUpdated', (predictionData) => renderPrediction(predictionData));
            Livewire.on('showCoinInfoModal', () => openModal('moneystepinfo-modal'));
            Livewire.on('coinInfoUpdated', (newMoneySteps) => {
                console.log('금액 설정이 완료되었습니다:', newMoneySteps);
                moneyArrStep = newMoneySteps;
                closeModal('moneystepinfo-modal');
            });

            // -----------------------------
            // 함수 정의
            // -----------------------------
            function getSelectedLogic() {
                const checkedBtn = el.logicBtnGroup.querySelector('[aria-checked="true"]');
                return checkedBtn ? checkedBtn.dataset.value : 'logic1';
            }

            function saveState() {
                try {
                    localStorage.setItem(STORAGE_KEY, jokboHistory);
                } catch (e) {}
            }

            function saveConsole() {
                try {
                    localStorage.setItem(CONSOLE_STORAGE_KEY, JSON.stringify(consoleMessages));
                } catch (e) {}
            }

            function addConsoleMessage(message, type = 'info') {
                if (!el.consoleBox) return;
                const msgObj = {
                    html: message,
                    type: type,
                    timestamp: Date.now()
                };
                consoleMessages.push(msgObj);
                const div = document.createElement('div');
                div.innerHTML = message;
                div.className = `console-message type-${type}`;
                el.consoleBox.appendChild(div);
                el.consoleBox.scrollTop = el.consoleBox.scrollHeight;
                saveConsole();
            }

            function clearConsole() {
                if (el.consoleBox) el.consoleBox.innerHTML = '';
                consoleMessages = [];
                saveConsole();
            }

            function applySettings() {
                if (el.interactiveArea) el.interactiveArea.style.display = currentSettings.chkconsole ? '' : 'none';
                if (el.copyLogBtn) el.copyLogBtn.style.display = currentSettings.chkcopylog ? '' : 'none';
                el.settingsToggles.forEach(toggle => {
                    const key = toggle.id;
                    const isChecked = currentSettings[key] || false;
                    toggle.setAttribute('aria-checked', isChecked);
                });
            }

            function saveSettings() {
                try {
                    localStorage.setItem(SETTINGS_KEY, JSON.stringify(currentSettings));
                } catch (e) {}
            }

            function openModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) modal.classList.remove('hidden');
            }

            function closeModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) modal.classList.add('hidden');
            }

            function updateLogicButtonsUI(selectedValue) {
                el.logicButtons.forEach(btn => {
                    const on = btn.dataset.value === selectedValue;
                    btn.setAttribute('aria-checked', on);
                });
            }

            function createGridItem(type, isHistory) {
                const node = document.createElement('div');
                const circleClass = type === 'P' ? 'grid-item-player' : 'grid-item-banker';
                if (isHistory) {
                    node.className = `history-item-wrapper grid-item-circle ${circleClass}`;
                    node.textContent = type;
                } else {
                    node.className = `grid-item-wrapper`;
                    node.innerHTML = `<div class="baccarat-circle ${circleClass}">${type}</div>`;
                }
                return node;
            }

            function computeRoadPositions(sequenceArr, MAX_ROW) {
                const positions = [];
                const occupied = {};

                function occKey(c, r) {
                    return `c${c}-r${r}`;
                }

                function checkCellEmpty(col, row) {
                    return col > 0 && row > 0 && row <= MAX_ROW && !occupied[occKey(col, row)];
                }

                function mark(col, row) {
                    occupied[occKey(col, row)] = true;
                }
                let lastType = null,
                    lastPlacedPos = null,
                    currentCol = 1,
                    lastRow = 0;
                for (const ch of sequenceArr) {
                    let targetCol, targetRow;
                    if (lastType === null) {
                        targetCol = 1;
                        targetRow = 1;
                    } else if (ch !== lastType) {
                        if (lastPlacedPos && lastPlacedPos.row === 1 && lastPlacedPos.col !== currentCol) {
                            targetCol = lastPlacedPos.col + 1;
                        } else {
                            targetCol = currentCol + 1;
                        }
                        targetRow = 1;
                    } else {
                        targetCol = currentCol;
                        targetRow = lastRow + 1;
                        if (targetRow > MAX_ROW || !checkCellEmpty(targetCol, targetRow)) {
                            if (!lastPlacedPos) {
                                targetCol = 1;
                                targetRow = 1;
                            } else {
                                targetRow = lastPlacedPos.row;
                                targetCol = lastPlacedPos.col + 1;
                                while (!checkCellEmpty(targetCol, targetRow)) {
                                    targetCol++;
                                    if (targetCol > 1000) break;
                                }
                                if (targetRow > MAX_ROW) {
                                    targetRow = MAX_ROW;
                                }
                            }
                        }
                    }
                    if (targetRow > MAX_ROW) {
                        let c = targetCol;
                        while (!checkCellEmpty(c, MAX_ROW)) {
                            c++;
                            if (c > 1000) break;
                        }
                        targetCol = c;
                        targetRow = MAX_ROW;
                    }
                    mark(targetCol, targetRow);
                    positions.push({
                        col: targetCol,
                        row: targetRow,
                        ch
                    });
                    if (ch !== lastType) currentCol = targetCol;
                    lastType = ch;
                    lastPlacedPos = {
                        col: targetCol,
                        row: targetRow
                    };
                    lastRow = lastPlacedPos.row;
                }
                return positions;
            }

            function redrawAllFromJokbo() {
                if (!el.mainRoadmapGrid) return;
                el.mainRoadmapGrid.innerHTML = '';
                Object.values(el.historyBoxes).forEach(h => {
                    if (h && h.grid) h.grid.innerHTML = '';
                });
                if (!jokboHistory || jokboHistory.length === 0) return;
                const arr = jokboHistory.split('').filter(c => c === 'P' || c === 'B');
                const mainPositions = computeRoadPositions(arr, 6);
                el.mainRoadmapGrid.style.gridTemplateRows = `repeat(6, var(--main-cell-size))`;
                mainPositions.forEach(it => {
                    const d = createGridItem(it.ch, false);
                    d.style.gridColumn = `${it.col}`;
                    d.style.gridRow = `${it.row}`;
                    el.mainRoadmapGrid.appendChild(d);
                });
                for (const key of [3, 4, 5, 6]) {
                    const hb = el.historyBoxes[key];
                    if (!hb || !hb.grid) continue;
                    const maxRow = parseInt(hb.box.dataset.rows, 10) || key;
                    hb.grid.style.gridTemplateRows = `repeat(${maxRow}, var(--history-cell-size))`;
                    let col = 1,
                        row = 1;
                    for (const char of arr) {
                        const item = createGridItem(char, true);
                        item.style.gridColumn = col;
                        item.style.gridRow = row;
                        hb.grid.appendChild(item);
                        row++;
                        if (row > maxRow) {
                            row = 1;
                            col++;
                        }
                    }
                }
                setTimeout(() => {
                    if (el.mainRoadmapContainer) el.mainRoadmapContainer.scrollLeft = el
                        .mainRoadmapContainer.scrollWidth;
                    Object.values(el.historyBoxes).forEach(hb => {
                        if (hb && hb.box) hb.box.scrollLeft = hb.box.scrollWidth;
                    });
                }, 0);
            }

            function renderPrediction(data) {
                if (!data || !data.predictions || data.predictions.length === 0) {
                    el.predictionHeader.innerHTML = '';
                    return;
                }
                const predictionHtml = data.predictions.map(p => {
                    if (!p.recommend) return '';
                    const circleClass = p.recommend === 'P' ? 'player' : 'banker';
                    const amountString = (p.amount || 0).toLocaleString();
                    const subTypeText = p.mae ? `[${p.mae}매]:` : `${p.sub_type}:`;
                    return `<div class="prediction-item">${subTypeText} (${p.step}단계) <span class="baccarat-circle ${circleClass}">${p.recommend}</span> <strong>${amountString}</strong></div>`;
                }).join('');
                el.predictionHeader.innerHTML = predictionHtml;
                const recommendationHtml = data.predictions.filter(p => p.recommend).map(p => {
                    const circleClass = p.recommend === 'P' ? 'player' : 'banker';
                    const amountString = (p.amount || 0).toLocaleString();
                    return `<span class="baccarat-circle ${circleClass}">${p.recommend}</span> ${amountString}`;
                }).join(' ');
                if (recommendationHtml) {
                    addConsoleMessage(`다음 추천은 ${recommendationHtml} 제시합니다.`, 'recommendation');
                }
            }

            function updateCounts() {
                el.playerCountSpan.textContent = (jokboHistory.match(/P/g) || []).length;
                el.bankerCountSpan.textContent = (jokboHistory.match(/B/g) || []).length;
                el.totalCountSpan.textContent = jokboHistory.length;
            }

            // -----------------------------
            // 이벤트 바인딩 (하나의 함수로 통합)
            // -----------------------------
            function bindEvents() {
                if (el.playerBtn) el.playerBtn.onclick = () => {
                    addConsoleMessage('플레이어를 선택했습니다.', 'player');
                    Livewire.emit('addResultRequest', 'P', getSelectedLogic(), currentSettings.chkvirtualbet);
                };
                if (el.bankerBtn) el.bankerBtn.onclick = () => {
                    addConsoleMessage('뱅커를 선택했습니다.', 'banker');
                    Livewire.emit('addResultRequest', 'B', getSelectedLogic(), currentSettings.chkvirtualbet);
                };
                if (el.undoBtn) el.undoBtn.onclick = () => {
                    addConsoleMessage('마지막 입력을 취소했습니다.', 'system');
                    Livewire.emit('undoRequest');
                };
                if (el.resetBtn) el.resetBtn.onclick = () => {
                    if (confirm('정말로 모든 기록을 초기화하시겠습니까?')) {
                        clearConsole();
                        addConsoleMessage('게임을 리셋하였습니다.', 'system');
                        Livewire.emit('resetRequest');
                    }
                };
                if (el.logicBtnGroup) el.logicBtnGroup.onclick = (e) => {
                    const btn = e.target.closest('.logic-btn');
                    if (!btn) return;
                    const v = btn.dataset.value;
                    updateLogicButtonsUI(v);
                    localStorage.setItem('selectedLogic', v);
                    addConsoleMessage(`'${v}' 로직으로 변경되었습니다.`, 'system');
                };
                if (el.viewToggleButton) el.viewToggleButton.onclick = () => {
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
                };
                document.onclick = (e) => {
                    if (e.target.closest('#open-settings-modal-btn')) {
                        e.preventDefault();
                        openModal('setting-box-modal');
                    }
                };
                el.settingsCloseBtns.forEach(btn => btn.onclick = () => closeModal(btn.dataset.modalId));
                el.settingsToggles.forEach(toggle => {
                    toggle.onclick = () => {
                        const key = toggle.id;
                        const isChecked = toggle.getAttribute('aria-checked') === 'true';
                        currentSettings[key] = !isChecked;
                        applySettings();
                        saveSettings();
                    };
                });
                if (el.setMoneyBtn) el.setMoneyBtn.onclick = () => {
                    const startAmount = parseInt(el.moneyInput.value, 10);
                    if (startAmount && startAmount > 0) {
                        Livewire.emit('setCoinInfoRequest', startAmount);
                    } else {
                        alert('올바른 시작 금액을 입력하세요.');
                        el.moneyInput.focus();
                    }
                };
                if (el.copyLogBtn) el.copyLogBtn.onclick = () => {
                    const logText = consoleMessages.map(msg => {
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = msg.html;
                        return tempDiv.textContent || tempDiv.innerText || '';
                    }).join('\n');
                    navigator.clipboard.writeText(logText).then(() => {
                        alert('콘솔 로그가 클립보드에 복사되었습니다.');
                    }, () => {
                        alert('로그 복사에 실패했습니다.');
                    });
                };
            }

            // -----------------------------
            // 초기화
            // -----------------------------
            function init() {
                console.log('Baccara System Initialized');
                const initialData = JSON.parse(baccaraContainer.dataset.initial || '{}');

                jokboHistory = localStorage.getItem(STORAGE_KEY) || initialData.jokboHistory || "";
                moneyArrStep = initialData.moneyArrStep || [];

                const defaultSettings = {
                    gamesetting: false,
                    soundsetting: false,
                    chkmoneyinfo: false,
                    chkconsole: true,
                    chkvirtualbet: true,
                    chkcopylog: true
                };
                try {
                    const savedSettings = JSON.parse(localStorage.getItem(SETTINGS_KEY));
                    currentSettings = {
                        ...defaultSettings,
                        ...savedSettings
                    };
                } catch (e) {
                    currentSettings = defaultSettings;
                }

                const savedLogic = localStorage.getItem('selectedLogic') || 'logic1';
                updateLogicButtonsUI(savedLogic);

                try {
                    const savedConsole = JSON.parse(localStorage.getItem(CONSOLE_STORAGE_KEY));
                    if (Array.isArray(savedConsole)) {
                        consoleMessages = savedConsole;
                    } else {
                        consoleMessages = [];
                    }
                } catch (e) {
                    consoleMessages = [];
                }

                el.consoleBox.innerHTML = '';
                if (consoleMessages.length > 0) {
                    consoleMessages.forEach(msg => {
                        const div = document.createElement('div');
                        div.innerHTML = msg.html;
                        div.className = `console-message type-${msg.type}`;
                        el.consoleBox.appendChild(div);
                    });
                } else {
                    addConsoleMessage('바카라 분석 시스템에 오신 것을 환영합니다.', 'system');
                }
                if (el.consoleBox) el.consoleBox.scrollTop = el.consoleBox.scrollHeight;

                redrawAllFromJokbo();
                updateCounts();
                applySettings();

                if (!moneyArrStep || moneyArrStep.length === 0) {
                    openModal('moneystepinfo-modal');
                }

                bindEvents();
            }

            // --- 최초 실행 ---
            init();
        });
    </script>
@endpush
