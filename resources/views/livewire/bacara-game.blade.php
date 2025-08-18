@php
    // 이 PHP 블록은 최초 페이지 로드 시에만 실행됩니다.
    // 서버의 초기 상태를 JavaScript로 전달하는 역할을 합니다.
    $initialData = json_encode([
        'jokboHistory' => $jokboHistory,
        'moneyArrStep' => $moneyArrStep,
    ]);
@endphp

{{-- Livewire 컴포넌트의 최상위 루트 요소입니다. --}}
<div class="baccara-container" data-initial="{{ $initialData }}">

    {{-- 예측 결과가 표시될 헤더 영역 --}}
    <header id="prediction-header" class="prediction-header"></header>

    {{-- 메인 콘텐츠 영역 --}}
    <main class="baccara-main-content">
        {{-- 플레이어, 뱅커, 로직 선택, 취소 버튼이 있는 컨트롤 패널 --}}
        <div wire:ignore class="flex flex-wrap justify-between items-center gap-2 mb-4">
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
                    <button type="button" role="radio" aria-checked="false" data-value="logic1"
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

        {{-- ▼▼▼ 1. wire:ignore 추가 ▼▼▼ --}}
        {{-- 이 div는 이제 Livewire에 의해 자동으로 업데이트되지 않으며, 오직 JavaScript로만 제어됩니다. --}}
        <div wire:ignore id="main-roadmap-container"
            class="relative border border-gray-700 rounded-md overflow-x-auto mb-4 shadow-inner checkerboard-bg main-grid-bg"
            style="height: calc(var(--main-cell-size) * 6);">
            <div id="roadmap-grid"></div>
        </div>

        {{-- ▼▼▼ 2. wire:ignore 추가 ▼▼▼ --}}
        <div wire:ignore class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            @for ($i = 3; $i <= 6; $i++)
                <div id="history-box-{{ $i }}" data-rows="{{ $i }}"
                    class="relative border border-gray-700 rounded-md shadow checkerboard-bg history-grid-bg overflow-x-auto"
                    style="height: calc(var(--history-cell-size) * {{ $i }});">
                    <div id="history-grid-{{ $i }}"></div>
                </div>
            @endfor
        </div>

        {{-- ▼▼▼ 3. wire:ignore 추가 ▼▼▼ --}}
        <div wire:ignore id="interactive-area" class="relative console-box-container">
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

        {{-- 리셋, 로그아웃 버튼 영역 --}}
        <div class="flex flex-wrap justify-between items-center gap-2 mt-4">
            <button id="reset-btn"
                class="px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm shadow">리셋</button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();"
                    class="px-4 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-sm shadow inline-block">로그아웃</a>
            </form>
        </div>

        {{-- 금액 설정 모달 (HTML 구조는 그대로 유지) --}}
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

        {{-- 환경 설정 모달 (HTML 구조는 그대로 유지) --}}
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
    {{-- JavaScript 코드는 여기에 작성됩니다. --}}
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
            const PREDICTION_STORAGE_KEY = `baccara_prediction_{{ auth()->user()->name }}`;

            // -----------------------------
            // Livewire 이벤트 수신
            // -----------------------------
            function getIconForPattern(mae, subType) {
                // ★★★ 이 부분에는 mae를 필터링하는 코드가 없어야 합니다. ★★★
                // (이전 답변의 주석은 제 실수였으며, 현재 코드가 정확합니다.)

                let iconClass = '';
                switch (subType) {
                    case '_pattern':
                        iconClass = 'ti ti-line-dashed';
                        break;
                    case 'tpattern':
                        iconClass = 'ti ti-letter-t-small';
                        break;

                    case 'upattern':
                        iconClass = 'ti ti-letter-u-small';
                        break;
                    case 'npattern':
                        iconClass = 'ti ti-letter-n-small';
                        break;
                    default:
                        return ''; // 일치하는 아이콘이 없으면 빈 문자열 반환
                }
                return `<i class="${iconClass}" style="font-size: 1.1rem; margin-right: 4px;"></i>`;
            }

            // ▼▼▼ 4. 'jokboUpdated' 리스너를 'itemAdded' 리스너로 교체 ▼▼▼
            // 서버에서 새로 추가된 아이템 정보만 받아서 클라이언트에서 처리합니다.
            Livewire.on('itemAdded', (data) => {
                // 1. JavaScript가 관리하는 족보 히스토리 문자열에 새 결과를 추가합니다.
                jokboHistory += data.type;

                // 2. 업데이트된 족보를 기반으로 모든 로드맵을 다시 그립니다.
                //    이 함수는 DOM을 직접 조작하므로 빠르고 깜빡임이 없습니다.
                redrawAllFromJokbo();

                // 3. 서버에서 받은 최신 카운트로 화면의 숫자를 업데이트합니다.
                if (data.counts) {
                    el.playerCountSpan.textContent = data.counts.player;
                    el.bankerCountSpan.textContent = data.counts.banker;
                    el.totalCountSpan.textContent = data.counts.total;
                }

                // 4. 변경된 족보 상태를 브라우저의 로컬 스토리지에 저장합니다.
                saveState();
            });

            // 'undo' 액션에 대한 이벤트 리스너 추가
            Livewire.on('itemRemoved', (data) => {
                // 1. 족보 히스토리에서 마지막 글자를 제거합니다.
                jokboHistory = jokboHistory.slice(0, -1);

                // 2. 단축된 족보를 기반으로 모든 로드맵을 다시 그립니다.
                redrawAllFromJokbo();

                // 3. 서버에서 받은 최신 카운트로 화면을 업데이트합니다.
                if (data.counts) {
                    el.playerCountSpan.textContent = data.counts.player;
                    el.bankerCountSpan.textContent = data.counts.banker;
                    el.totalCountSpan.textContent = data.counts.total;
                }

                // 4. 변경된 상태를 저장합니다.
                saveState();
            });

            // 'reset' 액션에 대한 이벤트 리스너 추가
            Livewire.on('gameReset', () => {
                jokboHistory = '';
                moneyArrStep = [];

                redrawAllFromJokbo(); // 빈 족보로 그리므로 모든 그리드가 깨끗해집니다.

                el.playerCountSpan.textContent = 0;
                el.bankerCountSpan.textContent = 0;
                el.totalCountSpan.textContent = 0;

                saveState(); // 빈 상태를 저장합니다.

                showToast('center-toast', '리셋이 완료되었습니다.', {
                    type: 'success', // 성공 타입 (초록색)
                    duration: 1500, // 1.5초 동안 표시
                    onComplete: () => {
                        // 토스트가 사라진 후, 금액 설정 모달을 엽니다.
                        clearConsole();
                        addConsoleMessage('게임을 리셋하였습니다.', 'system');
                        openModal('moneystepinfo-modal');
                    }
                });
            });

            // 나머지 이벤트 리스너는 그대로 유지됩니다.
            Livewire.on('predictionUpdated', (predictionData) => renderPrediction(predictionData));
            Livewire.on('showCoinInfoModal', () => openModal('moneystepinfo-modal'));
            Livewire.on('coinInfoUpdated', (newMoneySteps) => {
                console.log('금액 설정이 완료되었습니다:', newMoneySteps);
                moneyArrStep = newMoneySteps;
                closeModal('moneystepinfo-modal');
            });

            // -----------------------------
            // 함수 정의 (이 부분은 변경사항 없음)
            // -----------------------------
            function showToast(targetId, message, options = {}) {
                const config = {
                    type: 'info', // 기본 타입
                    duration: 2000, // 기본 지속시간 2초
                    position: 'center', // 기본 위치 'center'
                    onComplete: null, // ★★★ 콜백 함수를 위한 옵션
                    ...options
                };

                // 기존에 같은 타겟에 토스트가 있다면 즉시 제거
                const previousToast = document.getElementById(`toast-for-${targetId}`);
                if (previousToast) {
                    previousToast.remove();
                }

                // 새로운 토스트 요소 생성
                const toastElement = document.createElement('div');
                toastElement.id = `toast-for-${targetId}`;
                toastElement.className = `toast-base toast-type-${config.type} toast-position-${config.position}`;
                toastElement.innerHTML = message;

                document.body.appendChild(toastElement);

                // 애니메이션을 위해 잠시 후 등장 클래스 추가
                requestAnimationFrame(() => {
                    setTimeout(() => toastElement.classList.add('toast-visible'), 10);
                });

                // 설정된 시간 후 토스트 제거
                setTimeout(() => {
                    toastElement.classList.remove('toast-visible');

                    // 사라지는 애니메이션(0.3초)이 끝난 후 DOM에서 완전히 제거하고 콜백 실행
                    toastElement.addEventListener('transitionend', () => {
                        toastElement.remove();
                        // ★★★ 콜백 함수가 존재하면 여기서 실행 ★★★
                        if (typeof config.onComplete === 'function') {
                            config.onComplete();
                        }
                    }, {
                        once: true
                    }); // 이벤트는 한 번만 실행되도록 설정

                }, config.duration);
            }


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
                let coloredMessage = message;
                if (type === 'player') {
                    coloredMessage = message.replace('플레이어', '<span class="player-text">플레이어</span>');
                } else if (type === 'banker') {
                    coloredMessage = message.replace('뱅커', '<span class="banker-text">뱅커</span>');
                }

                const msgObj = {
                    html: coloredMessage,
                    type: type,
                    timestamp: Date.now()
                };
                consoleMessages.push(msgObj);
                const div = document.createElement('div');
                div.innerHTML = coloredMessage;
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
                console.log('renderPrediction', data);
                
                // 예측 데이터가 없거나 비어있으면 헤더를 비웁니다.
                if (!data || !data.predictions || data.predictions.length === 0) {
                    el.predictionHeader.innerHTML = '';
                    // 예측이 없을 때 콘솔 추천 메시지도 남기지 않도록 수정
                    logRecommendation([], data ? data.type : null);
                    return;
                }

                // 예측 데이터를 기반으로 HTML 문자열을 생성합니다.
                const predictionHtml = data.predictions.map(p => {
                    if (!p.recommend) return '';

                    // ★★★ 핵심 수정: p.mae와 p.sub_type을 모두 전달합니다. ★★★
                    let iconHtml = '';
                    if (data.type === 'logic1' && p.mae && p.sub_type) {
                        iconHtml = getIconForPattern(p.mae, p.sub_type);
                    }

                    const circleClass = p.recommend === 'P' ? 'player' : 'banker';
                    const amountString = (p.amount || 0).toLocaleString();
                    
                    // sub_type 텍스트를 더 명확하게 표시하도록 수정 (예: _pattern -> 패턴 1)
                    // 여기서는 기존 방식을 유지하되, 필요시 서버에서 이름을 보내도록 변경할 수 있습니다.
                    const subTypeText = p.mae ? `[${p.mae}매] ${p.sub_type}:` : `${p.sub_type}:`;

                    // 아이콘 HTML을 메시지 앞에 추가합니다.
                    return `<div class="prediction-item">${iconHtml}${subTypeText} (${p.step}단계) <span class="baccarat-circle ${circleClass}">${p.recommend}</span> <strong>${amountString}</strong></div>`;
                }).join('');

                el.predictionHeader.innerHTML = predictionHtml;

                localStorage.setItem(PREDICTION_STORAGE_KEY, JSON.stringify(data));
                logRecommendation(data.predictions, data.type);
            }

            function logRecommendation(predictions, logicType) {
                if (!Array.isArray(predictions) || predictions.length === 0) {
                    return;
                }

                const recommendationHtml = predictions.filter(p => p.recommend).map(p => {
                    // ★★★ 핵심 수정: p.mae와 p.sub_type을 모두 전달합니다. ★★★
                    let iconHtml = '';
                    if (logicType === 'logic1' && p.mae && p.sub_type) {
                        iconHtml = getIconForPattern(p.mae, p.sub_type);
                    }

                    const circleClass = p.recommend === 'P' ? 'player' : 'banker';
                    const amountString = (p.amount || 0).toLocaleString();

                    // 아이콘 HTML을 메시지 앞에 추가합니다.
                    return `${iconHtml}<span class="baccarat-circle ${circleClass}">${p.recommend}</span> ${amountString}`;
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
                    Livewire.emit('undoRequest', getSelectedLogic()); 
                };
                if (el.resetBtn) el.resetBtn.onclick = () => {
                    if (confirm('정말로 모든 기록을 초기화하시겠습니까?')) {
                        localStorage.removeItem(PREDICTION_STORAGE_KEY);
                        Livewire.emit('resetRequest');
                    }
                };
                if (el.logicBtnGroup) el.logicBtnGroup.onclick = (e) => {
                    const btn = e.target.closest('.logic-btn');
                    if (!btn) return;
                    const v = btn.dataset.value;
                    updateLogicButtonsUI(v);
                    localStorage.setItem('selectedLogic', v);

                    // ★★★ 여기에 토스트 호출 추가 ★★★
                    showToast('center-toast', `'${v}' 로직으로 변경되었습니다.`, {
                        type: 'info', // 정보 타입 (파란색)
                        duration: 1500, // 1.5초 동안 표시
                        onComplete: () => {
                            // 예시: 토스트가 사라진 후 콘솔에 로그를 남깁니다.
                            console.log(`'${v}' 로직으로 변경 완료.`);
                        }
                    });

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

                const savedPrediction = localStorage.getItem(PREDICTION_STORAGE_KEY);
                if (savedPrediction) {
                    try {
                        renderPrediction(JSON.parse(savedPrediction));
                    } catch (e) {
                        // 데이터가 깨져있으면 삭제
                        localStorage.removeItem(PREDICTION_STORAGE_KEY);
                    }
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
            init();
        });
    </script>
@endpush
