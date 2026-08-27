@push('header_actions')
    @if ($semId)
        <script>
            window.formatScore5 = window.formatScore5 || function (val) {
                if (val === null || val === undefined) return 'N/A';
                const str = String(val).trim().toUpperCase();
                if (str === '' || str === '-') return '-';
                if (str === 'N/A') return 'N/A';
                const num = parseFloat(str);
                if (isNaN(num)) return str;
                return num.toFixed(5);
            };

            if (window.semestralScoreRow) {
                const origSemestralScoreRow = window.semestralScoreRow;
                window.semestralScoreRow = function (initial) {
                    const obj = origSemestralScoreRow(initial);
                    obj.formatScore5 = window.formatScore5;
                    obj.computeAverage = function (schedule = true) {
                        let scores = [this.q, this.ql, this.t].map((v) => String(v || '').trim().toUpperCase());
                        let validNums = scores.filter((v) => v !== '' && !Number.isNaN(Number.parseFloat(v))).map((v) => Number.parseFloat(v));
                        if (validNums.length > 0) {
                            let sum = validNums.reduce((acc, curr) => acc + curr, 0);
                            this.avg = (sum / validNums.length).toFixed(5);
                        } else {
                            this.avg = scores.includes('N/A') ? 'N/A' : '';
                        }
                        if (schedule) this.scheduleSave();
                        window.dispatchEvent(new CustomEvent('recalculate-function-scores'));
                    };
                    return obj;
                };
            }

            window.headerFunctionScores = window.headerFunctionScores || function (includeStrategic) {
                return {
                    strategicScore: '0.00000',
                    coreScore: '0.00000',
                    supportScore: '0.00000',
                    finalScore: @js($finalRating ?: '0.00000'),
                    adjectival: @js($adjectivalRating ?: 'N/A'),
                    hasIncompleteTarget: Boolean(@js($this->hasIncompleteSemestralTargets())),
                    format5DecimalsWithoutRounding(num) {
                        const val = parseFloat(num);
                        if (isNaN(val) || val <= 0) return '0.00000';
                        const str = val.toString();
                        const parts = str.split('.');
                        const integerPart = parts[0];
                        let decimalPart = parts[1] || '';
                        if (decimalPart.length < 5) {
                            decimalPart = decimalPart.padEnd(5, '0');
                        } else {
                            decimalPart = decimalPart.substring(0, 5);
                        }
                        return `${integerPart}.${decimalPart}`;
                    },
                    initHeaderScores() {
                        const updateAll = () => {
                            this.calcFunctionScores();
                            this.checkIncompleteTargets();
                        };
                        this.$nextTick(() => updateAll());
                        setTimeout(() => updateAll(), 100);
                        setTimeout(() => updateAll(), 300);
                        setTimeout(() => updateAll(), 1000);
                        window.addEventListener('recalculate-function-scores', () => updateAll());
                        window.addEventListener('semestral-target-scores-saved', () => updateAll());
                        window.addEventListener('semestral-target-updated', () => {
                            this.$nextTick(() => updateAll());
                            setTimeout(() => updateAll(), 100);
                            setTimeout(() => updateAll(), 300);
                        });
                        document.addEventListener('input', () => updateAll(), true);
                        document.addEventListener('change', () => updateAll(), true);
                        document.addEventListener('keyup', () => updateAll(), true);
                        document.addEventListener('paste', () => updateAll(), true);

                        this.$watch('$wire.targetStatusFilter', () => {
                            this.$nextTick(() => updateAll());
                            setTimeout(() => updateAll(), 100);
                        });
                        this.$watch('$wire.categoryFilter', () => {
                            this.$nextTick(() => updateAll());
                            setTimeout(() => updateAll(), 100);
                        });
                        this.$watch('$wire.search', () => {
                            this.$nextTick(() => updateAll());
                            setTimeout(() => updateAll(), 100);
                        });
                        this.$watch('$wire.perPage', () => {
                            this.$nextTick(() => updateAll());
                            setTimeout(() => updateAll(), 100);
                        });

                        if (window.Livewire && !this._livewireHookRegistered) {
                            this._livewireHookRegistered = true;
                            window.Livewire.hook('commit', ({ succeed }) => {
                                succeed(() => {
                                    this.$nextTick(() => updateAll());
                                    setTimeout(() => updateAll(), 100);
                                });
                            });
                        }

                        if (window.MutationObserver && !this._targetObserver) {
                            this._targetObserver = new MutationObserver(() => updateAll());
                            this._targetObserver.observe(document.body, {
                                attributes: true,
                                childList: true,
                                subtree: true,
                                attributeFilter: ['data-has-attachments', 'data-na-quantity', 'data-na-quality', 'data-na-timeliness']
                            });
                        }
                    },
                    checkIncompleteTargets() {
                        const serverIncomplete = Boolean(@js($this->hasIncompleteSemestralTargets()));
                        const rows = document.querySelectorAll('tr[data-score-row]');
                        if (rows.length === 0) {
                            this.hasIncompleteTarget = serverIncomplete;
                            this.toggleImReadyButton(serverIncomplete);
                            return;
                        }

                        let foundIncomplete = false;

                        rows.forEach((row) => {
                            if (foundIncomplete) return;

                            const naQ = parseInt(row.dataset.naQuantity || '0', 10);
                            const naQl = parseInt(row.dataset.naQuality || '0', 10);
                            const naT = parseInt(row.dataset.naTimeliness || '0', 10);

                            const areAllNa = (naQ === 1 && naQl === 1 && naT === 1);

                            // If all 3 scores are N/A (na_quantity=1, na_quality=1, na_timeliness=1), the target is complete
                            if (areAllNa) return;

                            let q = '', ql = '', t = '', accomp = '', movs = '';

                            if (row._x_dataStack) {
                                for (let i = 0; i < row._x_dataStack.length; i++) {
                                    const stack = row._x_dataStack[i];
                                    if (stack) {
                                        if (stack.q !== undefined && stack.q !== null) q = String(stack.q).trim();
                                        if (stack.ql !== undefined && stack.ql !== null) ql = String(stack.ql).trim();
                                        if (stack.t !== undefined && stack.t !== null) t = String(stack.t).trim();
                                        if (stack.accomp !== undefined && stack.accomp !== null) accomp = String(stack.accomp).trim();
                                        if (stack.movs !== undefined && stack.movs !== null) movs = String(stack.movs).trim();
                                    }
                                }
                            }

                            if (!q) {
                                const qEl = row.querySelector("input[data-field='quantity']");
                                if (qEl) q = String(qEl.value || '').trim();
                            }
                            if (!ql) {
                                const qlEl = row.querySelector("input[data-field='quality']");
                                if (qlEl) ql = String(qlEl.value || '').trim();
                            }
                            if (!t) {
                                const tEl = row.querySelector("input[data-field='timeliness']");
                                if (tEl) t = String(tEl.value || '').trim();
                            }
                            if (!accomp) {
                                const accEl = row.querySelector("textarea[data-field='actual_accomp']");
                                if (accEl) accomp = String(accEl.value || '').trim();
                            }
                            if (!movs) {
                                const movEl = row.querySelector("textarea[data-field='target_movs']");
                                if (movEl) movs = String(movEl.value || '').trim();
                            }

                            const hasAttach = parseInt(row.dataset.hasAttachments || '0', 10);

                            const qUpper = q.toUpperCase();
                            const qlUpper = ql.toUpperCase();
                            const tUpper = t.toUpperCase();

                            const isQValid = (qUpper === 'N/A' || naQ === 1 || (q !== '' && q !== '0' && q !== '0.00' && !isNaN(parseFloat(q)) && parseFloat(q) > 0));
                            const isQlValid = (qlUpper === 'N/A' || naQl === 1 || (ql !== '' && ql !== '0' && ql !== '0.00' && !isNaN(parseFloat(ql)) && parseFloat(ql) > 0));
                            const isTValid = (tUpper === 'N/A' || naT === 1 || (t !== '' && t !== '0' && t !== '0.00' && !isNaN(parseFloat(t)) && parseFloat(t) > 0));

                            const isQEmpty = !isQValid;
                            const isQlEmpty = !isQlValid;
                            const isTEmpty = !isTValid;
                            const isAccompEmpty = (accomp === '');
                            const isMovsEmpty = (movs === '');
                            const isNoAttach = (hasAttach !== 1);

                            if (isQEmpty || isQlEmpty || isTEmpty || isAccompEmpty || isMovsEmpty || isNoAttach) {
                                foundIncomplete = true;
                            }
                        });

                        this.hasIncompleteTarget = foundIncomplete;
                        this.toggleImReadyButton(foundIncomplete);
                    },
                    toggleImReadyButton(isIncomplete) {
                        const btn = document.getElementById('im-ready-btn');
                        if (btn) {
                            if (isIncomplete) {
                                btn.style.display = 'none';
                            } else {
                                btn.style.display = '';
                            }
                        }
                    },
                    calcFunctionScores() {
                        const getRowAvg = (row) => {
                            if (row._x_dataStack) {
                                for (let i = 0; i < row._x_dataStack.length; i++) {
                                    if (row._x_dataStack[i] && row._x_dataStack[i].avg !== undefined && row._x_dataStack[i].avg !== null) {
                                        return row._x_dataStack[i].avg;
                                    }
                                }
                            }
                            const input = row.querySelector("input[data-field='average']");
                            if (input && input.value) return input.value;
                            if (row.dataset && row.dataset.rowAvg) return row.dataset.rowAvg;
                            return null;
                        };

                        const calcRawForCategory = (catId) => {
                            const rows = document.querySelectorAll("tr[data-kra-category='" + catId + "']");
                            let totalSum = 0;
                            let validCount = 0;
                            rows.forEach((row) => {
                                const val = getRowAvg(row);
                                if (val !== null && val !== undefined) {
                                    const str = String(val).trim().toUpperCase();
                                    if (str !== '' && str !== 'N/A' && str !== '-') {
                                        const num = parseFloat(str);
                                        if (!isNaN(num) && num > 0) {
                                            totalSum += num;
                                            validCount++;
                                        }
                                    }
                                }
                            });
                            return validCount > 0 ? (totalSum / validCount) : 0;
                        };

                        const strategicVal = includeStrategic ? calcRawForCategory(1) : 0;
                        const coreVal = calcRawForCategory(2);
                        const supportVal = calcRawForCategory(3);

                        this.strategicScore = this.format5DecimalsWithoutRounding(strategicVal);
                        this.coreScore = this.format5DecimalsWithoutRounding(coreVal);
                        this.supportScore = this.format5DecimalsWithoutRounding(supportVal);

                        let validCategories = 0;
                        let totalSum = 0;
                        if (includeStrategic && strategicVal > 0) { totalSum += strategicVal; validCategories++; }
                        if (coreVal > 0) { totalSum += coreVal; validCategories++; }
                        if (supportVal > 0) { totalSum += supportVal; validCategories++; }

                        let rawFinal = validCategories > 0 ? (totalSum / validCategories) : 0;

                        if (rawFinal > 0) {
                            this.finalScore = this.format5DecimalsWithoutRounding(rawFinal);
                            const calcVal = parseFloat(this.finalScore);
                            if (calcVal >= 5.00) this.adjectival = '{{ __("Outstanding") }}';
                            else if (calcVal >= 4.00) this.adjectival = '{{ __("Very Satisfactory") }}';
                            else if (calcVal >= 3.00) this.adjectival = '{{ __("Satisfactory") }}';
                            else if (calcVal >= 2.00) this.adjectival = '{{ __("Unsatisfactory") }}';
                            else if (calcVal > 0) this.adjectival = '{{ __("Poor") }}';
                            else this.adjectival = 'N/A';
                        } else {
                            this.finalScore = '0.00000';
                            this.adjectival = 'N/A';
                        }
                    }
                };
            };
        </script>
        <div class="flex items-center gap-2 text-xs" x-data="headerFunctionScores(@js($includeStrategicFunction))"
            x-init="initHeaderScores()">
            <div class="flex items-center gap-2 rounded-lg border border-border bg-muted/40 px-2.5 py-1">
                @if ($includeStrategicFunction)
                    <span class="text-muted-foreground font-medium">{{ __('Strategic Function Score:') }}</span>
                    <span class="font-bold text-foreground"
                        x-text="strategicScore">{{ $functionScores['strategicScore'] ?? '0.00000' }}</span>
                    <span class="text-muted-foreground font-medium ms-1">{{ __('Core Function Score:') }}</span>
                    <span class="font-bold text-foreground"
                        x-text="coreScore">{{ $functionScores['coreScore'] ?? '0.00000' }}</span>
                    <span class="text-muted-foreground font-medium ms-1">{{ __('Support Function Score:') }}</span>
                    <span class="font-bold text-foreground"
                        x-text="supportScore">{{ $functionScores['supportScore'] ?? '0.00000' }}</span>
                @else
                    <span class="text-muted-foreground font-medium">{{ __('Core Function Score:') }}</span>
                    <span class="font-bold text-foreground"
                        x-text="coreScore">{{ $functionScores['coreScore'] ?? '0.00000' }}</span>
                    <span class="text-muted-foreground font-medium ms-1">{{ __('Support Function Score:') }}</span>
                    <span class="font-bold text-foreground"
                        x-text="supportScore">{{ $functionScores['supportScore'] ?? '0.00000' }}</span>
                @endif
            </div>
            <div class="flex items-center gap-2 rounded-lg border border-border bg-muted/40 px-2.5 py-1">
                <span class="text-muted-foreground font-medium">{{ __('Final Rating:') }}</span>
                <span class="font-bold text-foreground"
                    x-text="finalScore">{{ $functionScores['finalScore'] ?? ($finalRating ?: '0.00000') }}</span>
                <span class="text-muted-foreground font-medium ms-1">{{ __('Adjectival:') }}</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400"
                    x-text="adjectival">{{ $functionScores['adjectival'] ?? ($adjectivalRating ?: 'N/A') }}</span>
            </div>
        </div>
    @endif
@endpush

<section class="w-full space-y-6" x-data="{
        scoreQueue: {},
        scoreSaveTimer: null,
        scoreSaveInFlight: false,
        scoreSaveError: '',
        scoreSavingField: '',
        queueScoreSave(item) {
            this.scoreSaveError = '';
            this.scoreQueue[item.id] = item;
            this.scoreSavingField = item.field || this.scoreSavingField || '';
            clearTimeout(this.scoreSaveTimer);
            this.scoreSaveTimer = setTimeout(() => this.flushScoreQueue(), 500);
        },
        async flushScoreQueue() {
            if (this.scoreSaveInFlight || Object.keys(this.scoreQueue).length === 0) return;

            const items = Object.values(this.scoreQueue);
            this.scoreQueue = {};
            this.scoreSaveInFlight = true;

            try {
                await $wire.batchSaveScores(items);
            } catch (error) {
                this.scoreSaveError = error?.message || @js(__('Unable to save scores. Your previous values were restored.'));
                window.dispatchEvent(new CustomEvent('semestral-target-scores-failed', {
                    detail: { items, message: this.scoreSaveError }
                }));
            } finally {
                this.scoreSaveInFlight = false;
                this.scoreSavingField = '';
                if (Object.keys(this.scoreQueue).length > 0) {
                    this.scoreSaveTimer = setTimeout(() => this.flushScoreQueue(), 500);
                }
            }
        }
    }" x-on:queue-score-save="queueScoreSave($event.detail)"
    x-on:semestral-target-updated.window="window.requestAnimationFrame(() => window.dispatchEvent(new CustomEvent('semestral-target-swap-reset')))"
    x-on:semestral-target-reload.window="setTimeout(() => window.location.reload(), 250)"
    x-on:open-new-tab.window="window.open($event.detail.url, '_blank')">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div class="space-y-1">
            <flux:heading size="lg" level="1">{{ $semesterHeading }}</flux:heading>
            <flux:subheading size="sm">{{ __('Review semestral target entries and performance indicators.') }}
            </flux:subheading>
        </div>
        <div>
            <flux:button href="{{ route('myratings.index') }}" wire:navigate size="sm" icon="arrow-left"
                variant="subtle">
                {{ __('Back to My Ratings') }}
            </flux:button>
        </div>
    </div>

    @if ($unauthorizedErrorMessage)
        <div
            class="rounded-2xl border border-red-300 bg-gradient-to-r from-red-500/10 via-rose-500/10 to-red-500/10 p-5 text-red-900 dark:border-red-800/80 dark:from-red-950/70 dark:via-rose-950/70 dark:to-red-950/70 dark:text-red-200 shadow-md flex items-start gap-4">
            <div class="flex size-10 items-center justify-center rounded-xl bg-red-600 text-white shadow-sm shrink-0">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="flex-1 space-y-1.5">
                <h4 class="font-bold text-base text-red-700 dark:text-red-300">{{ __('Access Denied') }}</h4>
                <p class="text-xs leading-relaxed text-muted-foreground dark:text-red-300/90 font-medium">
                    {{ $unauthorizedErrorMessage }}
                </p>
                <div class="pt-2">
                    <flux:button href="{{ route('myratings.index') }}" wire:navigate size="sm" icon="arrow-left"
                        class="bg-red-600 text-white hover:bg-red-700 dark:bg-red-600 dark:text-white dark:hover:bg-red-700 shadow-sm font-semibold">
                        {{ __('Return to My Ratings') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    <!-- Container for Login / User Profile Info -->
    <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full border-0 border-collapse">
                <tbody>
                    <tr class="align-top">
                        <td class="pr-8 whitespace-nowrap">
                            <div class="text-[11px] leading-none text-muted-foreground">{{ __('Full Name') }}</div>
                            <div class="mt-1 text-sm font-semibold leading-tight text-foreground uppercase">
                                {{ $fullName ?: '-' }}
                            </div>
                        </td>
                        <td class="pr-8 whitespace-nowrap">
                            <div class="text-[11px] leading-none text-muted-foreground">{{ __('Position') }}</div>
                            <div class="mt-1 text-sm font-semibold leading-tight text-foreground uppercase">
                                {{ $position ?: '-' }}
                            </div>
                        </td>
                        <td class="pr-8 whitespace-nowrap">
                            <div class="text-[11px] leading-none text-muted-foreground">{{ __('Designation') }}</div>
                            <div class="mt-1 text-sm font-semibold leading-tight text-foreground uppercase">
                                {{ $designation ?: '-' }}
                            </div>
                        </td>
                        <td class="pr-8 whitespace-nowrap">
                            <div class="text-[11px] leading-none text-muted-foreground">{{ __('Division Name') }}</div>
                            <div class="mt-1 text-sm font-semibold leading-tight text-foreground uppercase">
                                {{ $divisionName ?: '-' }}
                            </div>
                        </td>
                        <td class="whitespace-nowrap">
                            <div class="text-[11px] leading-none text-muted-foreground">{{ __('Section Name') }}</div>
                            <div class="mt-1 text-sm font-semibold leading-tight text-foreground uppercase">
                                {{ $sectionName ?: '-' }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Unified Tabbed Card Container -->
    <div x-data="{ activeTab: 'performance-indicator' }"
        class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden">
        <!-- Integrated Card Header -->
        <div class="bg-slate-50/90 dark:bg-zinc-900 px-6 sm:px-8 pt-4 pb-0">
            <nav class="flex flex-wrap items-center gap-2" aria-label="Tabs">
                <button type="button" x-on:click="activeTab = 'performance-indicator'"
                    :class="activeTab === 'performance-indicator'
                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm z-10 -mb-[2px]'
                        : 'bg-slate-100 dark:bg-zinc-800/90 text-slate-600 dark:text-zinc-300 border-slate-300/90 dark:border-zinc-700 hover:bg-slate-200/80 hover:text-slate-800 dark:hover:bg-zinc-700 z-0 -mb-px'"
                    style="border-radius: .5rem .5rem 0 0;"
                    class="group relative flex items-center gap-2 border-t border-x px-5 sm:px-6 py-3 text-xs font-bold transition-colors focus:outline-none select-none">
                    <span
                        :class="activeTab === 'performance-indicator' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-zinc-700 text-slate-600 dark:text-zinc-300 group-hover:text-foreground'"
                        class="flex size-5 text-[10px] items-center justify-center rounded-md transition-colors shrink-0">
                        <flux:icon icon="document-text" class="size-3.5" />
                    </span>
                    <span class="tracking-wide whitespace-nowrap">{{ __('Performance Indicator') }}</span>
                </button>

                <button type="button" x-on:click="activeTab = 'deleted-target'"
                    :class="activeTab === 'deleted-target'
                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm z-10 -mb-[2px]'
                        : 'bg-slate-100 dark:bg-zinc-800/90 text-slate-600 dark:text-zinc-300 border-slate-300/90 dark:border-zinc-700 hover:bg-slate-200/80 hover:text-slate-800 dark:hover:bg-zinc-700 z-0 -mb-px'"
                    style="border-radius: .5rem .5rem 0 0;"
                    class="group relative flex items-center gap-2 border-t border-x px-5 sm:px-6 py-3 text-xs font-bold transition-colors focus:outline-none select-none">
                    <span
                        :class="activeTab === 'deleted-target' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-zinc-700 text-slate-600 dark:text-zinc-300 group-hover:text-foreground'"
                        class="flex size-5 text-[10px] items-center justify-center rounded-md transition-colors shrink-0">
                        <flux:icon icon="trash" class="size-3.5" />
                    </span>
                    <span class="tracking-wide whitespace-nowrap">{{ __('Deleted Target') }}</span>
                </button>

                <button type="button" x-on:click="activeTab = 'checkpoint-changes'"
                    :class="activeTab === 'checkpoint-changes'
                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm z-10 -mb-[2px]'
                        : 'bg-slate-100 dark:bg-zinc-800/90 text-slate-600 dark:text-zinc-300 border-slate-300/90 dark:border-zinc-700 hover:bg-slate-200/80 hover:text-slate-800 dark:hover:bg-zinc-700 z-0 -mb-px'"
                    style="border-radius: .5rem .5rem 0 0;"
                    class="group relative flex items-center gap-2 border-t border-x px-5 sm:px-6 py-3 text-xs font-bold transition-colors focus:outline-none select-none">
                    <span
                        :class="activeTab === 'checkpoint-changes' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-zinc-700 text-slate-600 dark:text-zinc-300 group-hover:text-foreground'"
                        class="flex size-5 text-[10px] items-center justify-center rounded-md transition-colors shrink-0">
                        <flux:icon icon="clipboard-document-check" class="size-3.5" />
                    </span>
                    <span class="tracking-wide whitespace-nowrap">{{ __('Checkpoint Changes') }}</span>
                </button>

                <button type="button" x-on:click="activeTab = 'supervisors-feedback'"
                    :class="activeTab === 'supervisors-feedback'
                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm z-10 -mb-[2px]'
                        : 'bg-slate-100 dark:bg-zinc-800/90 text-slate-600 dark:text-zinc-300 border-slate-300/90 dark:border-zinc-700 hover:bg-slate-200/80 hover:text-slate-800 dark:hover:bg-zinc-700 z-0 -mb-px'"
                    style="border-radius: .5rem .5rem 0 0;"
                    class="group relative flex items-center gap-2 border-t border-x px-5 sm:px-6 py-3 text-xs font-bold transition-colors focus:outline-none select-none">
                    <span
                        :class="activeTab === 'supervisors-feedback' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-zinc-700 text-slate-600 dark:text-zinc-300 group-hover:text-foreground'"
                        class="flex size-5 text-[10px] items-center justify-center rounded-md transition-colors shrink-0">
                        <flux:icon icon="chat-bubble-left-right" class="size-3.5" />
                    </span>
                    <span class="tracking-wide whitespace-nowrap">{{ __('Supervisor\'s Feedback') }}</span>
                </button>

                <button type="button" x-on:click="activeTab = 'documentation'"
                    :class="activeTab === 'documentation'
                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm z-10 -mb-[2px]'
                        : 'bg-slate-100 dark:bg-zinc-800/90 text-slate-600 dark:text-zinc-300 border-slate-300/90 dark:border-zinc-700 hover:bg-slate-200/80 hover:text-slate-800 dark:hover:bg-zinc-700 z-0 -mb-px'"
                    style="border-radius: .5rem .5rem 0 0;"
                    class="group relative flex items-center gap-2 border-t border-x px-5 sm:px-6 py-3 text-xs font-bold transition-colors focus:outline-none select-none">
                    <span
                        :class="activeTab === 'documentation' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-zinc-700 text-slate-600 dark:text-zinc-300 group-hover:text-foreground'"
                        class="flex size-5 text-[10px] items-center justify-center rounded-md transition-colors shrink-0">
                        <flux:icon icon="folder" class="size-3.5" />
                    </span>
                    <span class="tracking-wide whitespace-nowrap">{{ __('Documentation') }}</span>
                </button>
            </nav>
        </div>
        <div class="relative z-20 w-full shrink-0 bg-emerald-600 dark:bg-emerald-500"
            style="height: 2px; min-height: 2px;" aria-hidden="true"></div>

        <!-- Card Body Content -->
        <div class="p-5 min-h-[350px]">
            <!-- Performance Indicator Tab Content (Default Active) -->
            <div x-show="activeTab === 'performance-indicator'">
                <div class="mb-4 space-y-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="size-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold shrink-0">
                            <flux:icon icon="chart-bar" class="size-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-foreground">{{ __('Performance Indicator') }}</h3>
                            <p class="text-xs text-muted-foreground">
                                {{ __('Review and manage the active semestral targets, scores, and accomplishment details.') }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between relative z-30 border-b border-border pb-4">
                        <div class="overflow-x-visible">
                            <table class="border-0 border-collapse">
                                <tbody>
                                    <tr class="align-top">
                                        <td class="px-2 py-1 whitespace-nowrap">
                                            <div class="relative">
                                                <flux:input wire:model.live.debounce.300ms="search"
                                                    :label="__('Search')"
                                                    :placeholder="__('Search semestral targets...')"
                                                    class="[&_input]:pr-8" />
                                                <div wire:loading wire:target="search"
                                                    class="absolute right-2.5 bottom-[9px] flex items-center justify-center pointer-events-none z-10 bg-card dark:bg-card">
                                                    <svg class="animate-spin size-4 text-emerald-600 dark:text-emerald-400"
                                                        xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                        </path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-2 py-1 whitespace-nowrap">
                                            <x-select2 wire:model.live.debounce.300ms="categoryFilter"
                                                :label="__('Category')" :placeholder="__('All categories')"
                                                :options="$categories" minWidth="160px" />
                                        </td>
                                        <td class="px-2 py-1 whitespace-nowrap">
                                            <x-select2 wire:model.live.debounce.300ms="targetStatusFilter"
                                                :label="__('Target Status')" :placeholder="__('All targets')"
                                                :options="[
        ['value' => 'checkpoint', 'label' => __('Has Checkpoint Target')],
        ['value' => 'incomplete', 'label' => __('Incomplete Target')],
    ]" minWidth="190px" :searchable="false" />
                                        </td>
                                        <td class="px-2 py-1 whitespace-nowrap">
                                            <x-select2 wire:model.live.debounce.300ms="perPage" :label="__('Records Per Page')" :placeholder="__('Select')" :options="$this->perPageOptions()"
                                                minWidth="120px" :searchable="false" />
                                        </td>
                                        @unless ($unauthorizedErrorMessage)
                                            <td class="px-1 py-1 whitespace-nowrap align-bottom">
                                                <div class="flex h-full items-end -ml-1">
                                                    <flux:button variant="primary" type="button" icon="arrow-path"
                                                        wire:click="resetFilters"
                                                        class="bg-slate-600 text-white hover:bg-slate-700 dark:bg-slate-500 dark:text-white dark:hover:bg-slate-400">
                                                        {{ __('Reset') }}
                                                    </flux:button>
                                                </div>
                                            </td>
                                        @endunless
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        @unless ($unauthorizedErrorMessage)
                            <div class="px-2 py-1 whitespace-nowrap align-bottom flex items-end gap-2">
                                @if (filled($this->getSemesterRecord()?->date_verified))
                                    @php
                                        $verifiedAt = \Illuminate\Support\Carbon::parse($this->getSemesterRecord()->date_verified)
                                            ->timezone('Asia/Manila')
                                            ->format('M d, Y h:i A');
                                    @endphp
                                    <button type="button" disabled
                                        class="inline-flex items-center gap-1.5 rounded-md border border-emerald-600 bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm cursor-default disabled:opacity-100 dark:border-emerald-600 dark:bg-emerald-600 dark:text-white">
                                        <flux:icon icon="check-badge" class="size-4" />
                                        <span>{{ __('Verified on :date', ['date' => $verifiedAt]) }}</span>
                                    </button>
                                @elseif ($this->getSemLockStatus() === 2)
                                    <flux:button variant="primary" type="button" icon="clock"
                                        wire:click="openWaitingVerificationModal"
                                        class="bg-amber-600 text-white hover:bg-amber-700 dark:bg-amber-600 dark:text-white dark:hover:bg-amber-700 font-semibold cursor-pointer">
                                        {{ __('Waiting for Verification') }}
                                    </flux:button>
                                @elseif ($this->isSemestralTargetLocked())
                                    <flux:button id="im-ready-btn" variant="primary" type="button" icon="check-circle"
                                        wire:click="openImReadyModal"
                                        style="{{ $this->hasIncompleteSemestralTargets() ? 'display: none;' : '' }}"
                                        class="bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-600 dark:text-white dark:hover:bg-emerald-700 font-semibold cursor-pointer">
                                        {{ __("I'm Ready") }}
                                    </flux:button>
                                @else
                                    <flux:button variant="primary" type="button" icon="lock-closed"
                                        wire:click="openLockConfirmModal"
                                        class="bg-amber-600 text-white hover:bg-amber-700 dark:bg-amber-600 dark:text-white dark:hover:bg-amber-700 font-semibold">
                                        {{ __('Save and Lock Semestral Target') }}
                                    </flux:button>
                                @endif

                                <flux:dropdown position="bottom-end">
                                    <flux:button variant="primary" icon="adjustments-horizontal"
                                        icon-trailing="chevron-down"
                                        class="bg-violet-600 text-white hover:bg-violet-700 dark:bg-violet-600 dark:text-white dark:hover:bg-violet-700">
                                        {{ __('Options') }}
                                    </flux:button>

                                    <flux:menu>
                                        @unless ($this->isSemestralTargetLocked())
                                            <flux:menu.item icon="document-duplicate" wire:click="openCopyModal">
                                                {{ __('Copy Target from Previous Semester') }}
                                            </flux:menu.item>
                                        @endunless
                                        @if ($this->isSemestralTargetLocked() && $this->getSemLockStatus() !== 2)
                                            <flux:menu.item icon="lock-open" wire:click="openUnlockConfirmModal">
                                                {{ __('Unlock Semestral Target') }}
                                            </flux:menu.item>
                                        @endif
                                        @unless ($this->isSemestralTargetLocked())
                                            <flux:menu.separator />
                                            <flux:menu.item icon="arrow-path" wire:click="openRecoverModal">
                                                {{ __('Recover Deleted Targets') }}
                                            </flux:menu.item>
                                        @endunless
                                    </flux:menu>
                                </flux:dropdown>

                                <flux:dropdown position="bottom-end">
                                    <flux:button variant="primary" icon="printer" icon-trailing="chevron-down"
                                        class="bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-600 dark:text-white dark:hover:bg-emerald-700">
                                        {{ __('Print') }}
                                    </flux:button>

                                    <flux:menu>
                                        <flux:menu.item icon="document-text" wire:click="printIpcrf">
                                            {{ __('Print IPCR-F') }}
                                        </flux:menu.item>
                                        @if ($semId)
                                            <flux:menu.item icon="clipboard-document-check" as="a"
                                                href="{{ route('myratings.semestral-target.print-checkpoint', ['sem_id' => $semId]) }}"
                                                target="_blank">
                                                {{ __('Print Checkpoint') }}
                                            </flux:menu.item>
                                        @else
                                            <flux:menu.item icon="clipboard-document-check" wire:click="printCheckpoint">
                                                {{ __('Print Checkpoint') }}
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        @endunless
                    </div>
                </div>

                @php
                    $isSemesterLocked = $this->isSemestralTargetLocked();
                    $semLock = $this->getSemLockStatus();
                @endphp

                <div x-cloak x-show="scoreSaveError" x-transition
                    class="mb-3 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700" role="alert"
                    x-text="scoreSaveError"></div>

                @include('livewire.semestral-target.pagination', [
                    'paginator' => $semestralTargets,
                    'keyPrefix' => 'top',
                    'paginationClass' => 'mb-4',
                ])

                <div class="relative w-full rounded-xl border border-border" wire:loading.class="opacity-60"
                    wire:target="search,categoryFilter,targetStatusFilter,perPage,resetFilters,setPage,previousPage,nextPage">
                    <div wire:loading.flex
                        wire:target="search,categoryFilter,targetStatusFilter,perPage,resetFilters,setPage,previousPage,nextPage"
                        class="absolute inset-x-0 top-14 z-20 justify-center pointer-events-none">
                        <div
                            class="flex items-center gap-2 rounded-full border border-border bg-card px-3 py-1.5 text-xs font-semibold shadow-md">
                            <svg class="size-4 animate-spin text-emerald-600" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            {{ __('Loading targets...') }}
                        </div>
                    </div>
                    <table class="w-full table-fixed border-separate border-spacing-0 text-sm">
                        @if ($isSemesterLocked)
                            <colgroup>
                                <col style="width: 4%;">
                                <col style="width: 14%;">
                                <col style="width: 14%;">
                                <col style="width: 14%;">
                                <col style="width: 10%;">
                                <col style="width: 10%;">
                                <col style="width: 10%;">
                                <col style="width: 5%;">
                                <col style="width: 10%;">
                                <col style="width: 9%;">
                            </colgroup>
                        @else
                            <colgroup>
                                <col style="width: 5%;">
                                <col style="width: 17%;">
                                <col style="width: 17%;">
                                <col style="width: 16%;">
                                <col style="width: 16%;">
                                <col style="width: 16%;">
                                <col style="width: 16%;">
                                <col style="width: 13%;">
                            </colgroup>
                        @endif
                        <thead
                            class="bg-muted/50 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th class="border-b border-r border-border px-3 py-3 text-center whitespace-nowrap first:rounded-tl-xl"
                                    style="border-right: 1px solid var(--border);">
                                    {{ __('Action') }}
                                </th>
                                <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap"
                                    style="border-right: 1px solid var(--border);">
                                    {{ __('Key Result Area') }}
                                </th>
                                <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap"
                                    style="border-right: 1px solid var(--border);">
                                    {{ __('Success Indicator') }}
                                </th>
                                @if ($isSemesterLocked)
                                    <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap"
                                        style="border-right: 1px solid var(--border);">
                                        {{ __('Actual Accomplishment') }}
                                    </th>
                                @endif
                                <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap"
                                    style="border-right: 1px solid var(--border);">
                                    {{ $isSemesterLocked ? __('Efficiency') : __('RG Efficiency') }}
                                </th>
                                <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap"
                                    style="border-right: 1px solid var(--border);">
                                    {{ $isSemesterLocked ? __('Quality') : __('RG Quality') }}
                                </th>
                                <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap"
                                    style="border-right: 1px solid var(--border);">
                                    {{ $isSemesterLocked ? __('Timeliness') : __('RG Timeliness') }}
                                </th>
                                @if ($isSemesterLocked)
                                    <th class="border-b border-r border-border px-2 py-3 text-center whitespace-nowrap"
                                        style="border-right: 1px solid var(--border);">
                                        {{ __('AVE') }}
                                    </th>
                                @endif
                                <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap"
                                    style="border-right: 1px solid var(--border);">
                                    {{ $isSemesterLocked ? __('MOVs') : __('RG MOVs') }}
                                </th>
                                <th class="border-b border-l border-border px-3 py-3 whitespace-nowrap last:rounded-tr-xl"
                                    style="border-left: 1px solid var(--border);">
                                    {{ $isSemesterLocked ? __('Remarks') : __('RG Remarks') }}
                                </th>
                            </tr>
                        </thead>

                        @php
                            $isAllCategories = empty($categoryFilter);
                            $rowsByCategory = collect($semestralTargets->items())
                                ->groupBy(fn($row) => (int) ($row->kra_category ?? 0))
                                ->map(fn($rows) => $rows->groupBy(fn($row) => (int) ($row->sem_target_id ?? 0)));
                        @endphp

                        @foreach ($visibleCategories as $category)
                            @php
                                $groupedByIndicator = $rowsByCategory->get((int) $category->value, collect());
                                $hideIfEmptySlice = $isAllCategories && $groupedByIndicator->isEmpty();
                            @endphp

                            @if (!$hideIfEmptySlice)
                                <tbody wire:key="semestral-target-category-heading-{{ $category->value }}"
                                    x-on:dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
                                    x-on:drop.prevent="dropOn($event, { type: 'category', kra: {{ (int) $category->value }}, indicatorId: 0, itemId: 0 })">
                                    <tr class="bg-muted/30">
                                        <td colspan="{{ $isSemesterLocked ? 10 : 8 }}" class="border-b border-border px-3 py-2">
                                            <div class="font-bold text-foreground">
                                                <span
                                                    class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ $category->label }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>

                                @forelse ($groupedByIndicator as $indId => $rows)
                                    @php
                                        $groupRows = $rows->values();
                                    @endphp
                                    <livewire:semestral-target.indicator-rows :indicator-id="(int) $indId"
                                        :rows="$groupRows->map(fn($row) => (array) $row)->all()"
                                        :is-semester-locked="$isSemesterLocked" :sem-lock="$semLock"
                                        :date-verified="$this->getSemesterRecord()?->date_verified"
                                        :key="'semestral-target-indicator-' . $indId" />
                                @empty
                                    @if (!$isAllCategories)
                                        <tbody wire:key="semestral-target-empty-{{ $category->value }}">
                                            <tr>
                                                <td colspan="{{ $isSemesterLocked ? 10 : 8 }}"
                                                    class="border-b border-border px-3 py-6 text-center text-muted-foreground">
                                                    {{ __('No semestral target entries under :category', ['category' => $category->label]) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    @endif
                                @endforelse
                            @endif
                        @endforeach

                        @if ($semestralTargets->isEmpty())
                            <tbody wire:key="semestral-target-empty-total">
                                <tr>
                                    <td colspan="{{ $isSemesterLocked ? 10 : 8 }}"
                                        class="border-b border-border px-3 py-6 text-center text-muted-foreground">
                                        {{ __('No semestral target entries found.') }}
                                    </td>
                                </tr>
                            </tbody>
                        @endif
                    </table>
                </div>

                @include('livewire.semestral-target.pagination', [
                    'paginator' => $semestralTargets,
                    'keyPrefix' => 'bottom',
                    'paginationClass' => 'mt-4',
                ])
            </div>

            <!-- Deleted Target Tab Content -->
            <div x-show="activeTab === 'deleted-target'" x-cloak>
                <div class="space-y-6">
                    @php
                        $deletedTargetsPaginator = $this->deletedTargetsData();
                    @endphp

                    <div class="flex items-center gap-3">
                        <div
                            class="size-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold shrink-0">
                            <flux:icon icon="trash" class="size-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-foreground">{{ __('Deleted Targets Repository') }}</h3>
                            <p class="text-xs text-muted-foreground">
                                {{ __('View deleted semestral targets from ipc_sem_target_edit_histories.field_name = deleted.') }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full border-collapse">
                            <tbody>
                                <tr class="align-top">
                                    <td class="px-0 py-1 whitespace-nowrap">
                                        <div class="relative">
                                            <flux:input wire:model.live.debounce.300ms="deletedTargetSearch"
                                                :placeholder="__('Search deleted target, justification, or deleted by...')" />
                                        </div>
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <flux:button type="button" variant="ghost" size="sm"
                                                wire:click="$set('deletedTargetSearch', '')">
                                                {{ __('Clear') }}
                                            </flux:button>
                                        </div>
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap">
                                        @if ((int) ($semLock ?? 0) === 0)
                                            <flux:button variant="primary" size="sm" icon="arrow-path"
                                                wire:click="openRecoverModal"
                                                class="bg-amber-600 text-white hover:bg-amber-700">
                                                {{ __('Recover Deleted Targets') }}
                                            </flux:button>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-border">
                        <table class="w-full border-collapse text-xs">
                            <thead class="bg-muted/80 text-left font-semibold uppercase text-muted-foreground">
                                <tr>
                                    <th class="border-r border-border px-3 py-2.5">{{ __('KRA Category') }}</th>
                                    <th class="border-r border-border px-3 py-2.5">
                                        {{ __('Key Result Area / Activity') }}
                                    </th>
                                    <th class="border-r border-border px-3 py-2.5">{{ __('Deleted Date & User') }}</th>
                                    <th class="border-r border-border px-3 py-2.5">{{ __('Justification') }}</th>
                                    <th class="px-3 py-2.5 text-center">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($deletedTargetsPaginator as $item)
                                    <tr class="border-t border-border/60 hover:bg-muted/20">
                                        <td
                                            class="border-r border-border px-3 py-2 align-top font-semibold text-slate-800 dark:text-zinc-200">
                                            {{ $item['kra_category_label'] }}
                                        </td>
                                        <td class="border-r border-border px-3 py-2 align-top">
                                            <div class="font-bold text-slate-900 dark:text-zinc-100">{{ $item['activity'] }}
                                            </div>
                                            @if (!empty($item['description']))
                                                <div class="text-[11px] text-muted-foreground mt-1">
                                                    {!! nl2br(e($item['description'])) !!}
                                                </div>
                                            @endif
                                        </td>
                                        <td
                                            class="border-r border-border px-3 py-2 align-top text-muted-foreground whitespace-nowrap">
                                            <div>{{ $item['deleted_at'] }}</div>
                                            <div class="text-[10px] font-semibold text-slate-500">{{ $item['user_name'] }}
                                            </div>
                                        </td>
                                        <td class="border-r border-border px-3 py-2 align-top italic text-foreground">
                                            {!! nl2br(e($item['justification'])) !!}
                                        </td>
                                        <td class="px-3 py-2 align-top text-center">
                                            @if ((int) ($semLock ?? 0) === 0)
                                                <flux:button size="xs" variant="primary"
                                                    class="bg-emerald-600 text-white hover:bg-emerald-700 font-semibold"
                                                    wire:click="recoverTarget({{ $item['sem_target_id'] }})">
                                                    {{ __('Restore') }}
                                                </flux:button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-8 text-center text-muted-foreground">
                                            {{ __('No deleted targets found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col items-center justify-between gap-3 sm:flex-row pt-1">
                        <div class="text-xs text-muted-foreground">
                            @if ($deletedTargetsPaginator->total() > 0)
                                                        {{ __('Showing deleted targets :from-:to of :total', [
                                    'from' => $deletedTargetsPaginator->firstItem(),
                                    'to' => min($deletedTargetsPaginator->currentPage() * $deletedTargetsPaginator->perPage(), $deletedTargetsPaginator->total()),
                                    'total' => $deletedTargetsPaginator->total(),
                                ]) }}
                            @else
                                {{ __('No deleted targets found') }}
                            @endif
                        </div>

                        @if ($deletedTargetsPaginator->lastPage() > 1)
                            <nav class="flex flex-wrap items-center justify-center gap-1"
                                aria-label="{{ __('Deleted target pagination') }}">
                                <flux:button variant="ghost" size="sm" type="button" wire:click="setDeletedTargetPage(1)"
                                    :disabled="$deletedTargetsPaginator->onFirstPage()">
                                    {{ __('First') }}
                                </flux:button>
                                <flux:button variant="ghost" size="sm" type="button" wire:click="previousDeletedTargetPage"
                                    :disabled="$deletedTargetsPaginator->onFirstPage()">
                                    {{ __('Previous') }}
                                </flux:button>
                                @for ($page = 1; $page <= $deletedTargetsPaginator->lastPage(); $page++)
                                    <flux:button size="sm" type="button" wire:click="setDeletedTargetPage({{ $page }})"
                                        variant="{{ $page === $deletedTargetsPaginator->currentPage() ? 'primary' : 'ghost' }}">
                                        {{ $page }}
                                    </flux:button>
                                @endfor
                                <flux:button variant="ghost" size="sm" type="button" wire:click="nextDeletedTargetPage"
                                    :disabled="!$deletedTargetsPaginator->hasMorePages()">
                                    {{ __('Next') }}
                                </flux:button>
                                <flux:button variant="ghost" size="sm" type="button"
                                    wire:click="setDeletedTargetPage({{ $deletedTargetsPaginator->lastPage() }})"
                                    :disabled="!$deletedTargetsPaginator->hasMorePages()">
                                    {{ __('Last') }}
                                </flux:button>
                            </nav>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Checkpoint Changes Tab Content -->
            <div x-show="activeTab === 'checkpoint-changes'" x-cloak>
                @php
                    $checkpointChangeData = $this->checkpointChangeRows();
                    $checkpointActiveRows = $checkpointChangeData['activeRows'];
                    $checkpointDeletedRows = $checkpointChangeData['deletedRows'];
                @endphp
                <div class="space-y-6">
                    <div class="flex items-center gap-3">
                        <div
                            class="size-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold shrink-0">
                            <flux:icon icon="clipboard-document-check" class="size-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-foreground">{{ __('Checkpoint Changes') }}</h3>
                            <p class="text-xs text-muted-foreground">
                                {{ __('Complete checkpoint amendment history based on the print-checkpoint table.') }}
                            </p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-border">
                        <table class="w-full border-collapse text-xs">
                            <thead class="bg-muted/80 text-left font-semibold uppercase text-muted-foreground">
                                <tr>
                                    <th class="border-r border-border px-3 py-2.5 w-[5%] text-center">{{ __('No.') }}</th>
                                    <th class="border-r border-border px-3 py-2.5 w-[32%]">{{ __('Original Success Indicator') }}</th>
                                    <th class="border-r border-border px-3 py-2.5 w-[32%]">{{ __('Proposed Amendment') }}</th>
                                    <th class="border-r border-border px-3 py-2.5 w-[15%]">{{ __('Justification') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $checkpointRowIndex = 1; @endphp
                                @forelse ($checkpointActiveRows as $row)
                                    @if ($row->is_new_target ?? false)
                                        <tr class="bg-slate-100 dark:bg-zinc-800 font-bold border-y border-border">
                                            <td colspan="5" class="py-1.5 px-3 text-center uppercase tracking-wide text-xs text-black">
                                                {{ '--- NEW TARGET ADDED: ' . ($row->activity_title ?: 'NEW ENTRY') . ' ---' }}
                                            </td>
                                        </tr>
                                    @endif
                                    <tr class="border-t border-border/60 align-top hover:bg-muted/20">
                                        <td class="border-r border-border px-3 py-2 text-center font-bold">{{ $checkpointRowIndex++ }}</td>
                                        <td class="border-r border-border px-3 py-2">
                                            <div class="space-y-2">
                                                @foreach ($row->target_fields as $field)
                                                    <div>
                                                        <span class="font-semibold italic">{{ $field->field_label }}</span><br>
                                                        <span class="text-foreground">{!! nl2br(e($field->old_value)) !!}</span>
                                                    </div>
                                                @endforeach
                                                @foreach ($row->item_groups as $itemGroup)
                                                    <div class="@if(count($row->item_groups) > 1 && (!$loop->first || !empty($row->target_fields))) pt-2 border-t border-border/60 @endif">
                                                        @if (!empty($itemGroup->item_label))
                                                            <div class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                                                                {{ $itemGroup->item_label }}
                                                            </div>
                                                        @endif
                                                        @foreach ($itemGroup->fields as $field)
                                                            <div>
                                                                <span class="font-semibold italic">{{ $field->field_label }}</span><br>
                                                                <span class="text-foreground">{!! nl2br(e($field->old_value)) !!}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="border-r border-border px-3 py-2">
                                            <div class="space-y-2">
                                                @foreach ($row->target_fields as $field)
                                                    <div>
                                                        <span class="font-semibold italic">{{ $field->field_label }}</span><br>
                                                        <span class="text-foreground">{!! nl2br(e($field->new_value)) !!}</span>
                                                    </div>
                                                @endforeach
                                                @foreach ($row->item_groups as $itemGroup)
                                                    <div class="@if(count($row->item_groups) > 1 && (!$loop->first || !empty($row->target_fields))) pt-2 border-t border-border/60 @endif">
                                                        @if (!empty($itemGroup->item_label))
                                                            <div class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                                                                {{ $itemGroup->item_label }}
                                                            </div>
                                                        @endif
                                                        @foreach ($itemGroup->fields as $field)
                                                            <div>
                                                                <span class="font-semibold italic">{{ $field->field_label }}</span><br>
                                                                <span class="text-foreground">{!! nl2br(e($field->new_value)) !!}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="border-r border-border px-3 py-2">
                                            <span class="text-foreground">{!! nl2br(e($row->justification)) !!}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-sm font-medium text-muted-foreground">
                                            {{ __('No checkpoint changes recorded yet.') }}
                                        </td>
                                    </tr>
                                @endforelse

                                @if ($checkpointDeletedRows->isNotEmpty())
                                    <tr class="bg-slate-100 dark:bg-zinc-800 font-bold border-y border-border text-red-900 dark:text-red-300">
                                        <td colspan="4" class="py-1.5 px-3 text-center uppercase tracking-wide text-xs">
                                            --- DELETED TARGETS ---
                                        </td>
                                    </tr>
                                    @foreach ($checkpointDeletedRows as $row)
                                        <tr class="border-t border-border/60 align-top hover:bg-muted/20">
                                            <td class="border-r border-border px-3 py-2 text-center font-bold">{{ $checkpointRowIndex++ }}</td>
                                            <td class="border-r border-border px-3 py-2">
                                                <div class="space-y-2">
                                                    @foreach ($row->target_fields as $field)
                                                        <div>
                                                            <span class="font-semibold italic">{{ $field->field_label }}</span><br>
                                                            <span class="text-foreground">{!! nl2br(e($field->old_value)) !!}</span>
                                                        </div>
                                                    @endforeach
                                                    @foreach ($row->item_groups as $itemGroup)
                                                        <div class="@if(count($row->item_groups) > 1 && (!$loop->first || !empty($row->target_fields))) pt-2 border-t border-border/60 @endif">
                                                            @if (!empty($itemGroup->item_label))
                                                                <div class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                                                                    {{ $itemGroup->item_label }}
                                                                </div>
                                                            @endif
                                                            @foreach ($itemGroup->fields as $field)
                                                                <div>
                                                                    <span class="font-semibold italic">{{ $field->field_label }}</span><br>
                                                                    <span class="text-foreground">{!! nl2br(e($field->old_value)) !!}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td class="border-r border-border px-3 py-2">
                                                <div class="space-y-2">
                                                    @foreach ($row->target_fields as $field)
                                                        <div>
                                                            <span class="font-semibold italic">{{ $field->field_label }}</span><br>
                                                            <span class="text-foreground">{!! nl2br(e($field->new_value)) !!}</span>
                                                        </div>
                                                    @endforeach
                                                    @foreach ($row->item_groups as $itemGroup)
                                                        <div class="@if(count($row->item_groups) > 1 && (!$loop->first || !empty($row->target_fields))) pt-2 border-t border-border/60 @endif">
                                                            @if (!empty($itemGroup->item_label))
                                                                <div class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                                                                    {{ $itemGroup->item_label }}
                                                                </div>
                                                            @endif
                                                            @foreach ($itemGroup->fields as $field)
                                                                <div>
                                                                    <span class="font-semibold italic">{{ $field->field_label }}</span><br>
                                                                    <span class="text-foreground">{!! nl2br(e($field->new_value)) !!}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td class="border-r border-border px-3 py-2">
                                                <span class="text-foreground">{!! nl2br(e($row->justification)) !!}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Supervisor's Feedback Tab Content -->
            <div x-show="activeTab === 'supervisors-feedback'" x-cloak>
                <div class="space-y-6">
                    @php
                        $semesterRecord = $this->getSemesterRecord();
                        $isVerified = filled($semesterRecord?->date_verified);
                    @endphp

                    @if ((int) ($semLock ?? 0) === 3)
                        <div
                            class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                            {{ __('This semestral target is still waiting for verification.') }}
                        </div>
                    @endif

                    <div class="flex items-center gap-3">
                        <div
                            class="size-12 rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold shrink-0">
                            <flux:icon icon="chat-bubble-left-right" class="size-7" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-foreground">
                                {{ __('Supervisor\'s Evaluation & Feedback') }}
                            </h3>
                            <p class="text-xs text-muted-foreground">
                                {{ __('Official review remarks, notes, and coaching feedback from your immediate supervisor.') }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="my-5 flex flex-col items-center justify-center text-center py-10 space-y-3.5 rounded-xl border border-dashed border-border bg-muted/20">
                        <div
                            class="size-14 rounded-full {{ $isVerified ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-amber-500/15 text-amber-600 dark:text-amber-400' }} flex items-center justify-center">
                            @if ($isVerified)
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                    stroke-linecap="round" stroke-linejoin="round" class="size-7" aria-hidden="true">
                                    <path d="M2.5 11.5h4v10h-4z" />
                                    <path
                                        d="M6.5 11.5 11 4.5c.7-1.1 2.4-1 3 .2l.7 1.5c.2.4.3.8.3 1.3v3.5h5c1.1 0 2 .9 2 2 0 .3-.1.7-.2 1l-2.1 6.4c-.3.9-1.1 1.6-2.1 1.6H8.6c-.8 0-1.5-.5-1.8-1.2l-2.4-5.4" />
                                </svg>
                            @else
                                <flux:icon icon="chat-bubble-left-right" class="size-8" />
                            @endif
                        </div>
                        <h4
                            class="text-xl font-extrabold {{ $isVerified ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }} tracking-wide">
                            {{ $isVerified ? __('Congratulations! You are now Verified.') : __('Pending Verification') }}
                        </h4>
                        <p class="text-sm text-muted-foreground max-w-md">
                            {{ $isVerified
    ? __('This semestral target has been verified. Supervisor feedback, review notes, and approval details are now available below.')
    : __('This semestral target is still pending verification. Supervisor feedback and review notes will appear once verification is completed.') }}
                        </p>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <h4 class="text-sm font-semibold text-foreground">{{ __('Areas of Improvement') }}</h4>
                            <p class="text-xs text-muted-foreground">
                                {{ __('Supervisor feedback is presented below in a clean four-card layout for easier reading.') }}
                            </p>
                        </div>

                        @php
                            $areasImprovementRecord = $this->areasImprovementItems()->first();
                        @endphp

                        @if ($areasImprovementRecord)
                            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                <div class="rounded-2xl border border-border bg-card/80 p-4 shadow-sm">
                                    <div class="mb-3 flex items-center gap-2">
                                        <div
                                            class="flex size-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                            <flux:icon icon="sparkles" class="size-5" />
                                        </div>
                                        <div>
                                            <p
                                                class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">
                                                {{ __('Areas of Improvement') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="rounded-xl bg-muted/30 px-3 py-3 text-sm leading-6 text-foreground">
                                        {!! nl2br(e($areasImprovementRecord->areas_improvement ?? '-')) !!}
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-border bg-card/80 p-4 shadow-sm">
                                    <div class="mb-3 flex items-center gap-2">
                                        <div
                                            class="flex size-10 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400">
                                            <flux:icon icon="clipboard-document-list" class="size-5" />
                                        </div>
                                        <div>
                                            <p
                                                class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">
                                                {{ __('Development Activities') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="rounded-xl bg-muted/30 px-3 py-3 text-sm leading-6 text-foreground">
                                        {!! nl2br(e($areasImprovementRecord->development_activities ?? '-')) !!}
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-border bg-card/80 p-4 shadow-sm">
                                    <div class="mb-3 flex items-center gap-2">
                                        <div
                                            class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                            <flux:icon icon="shield-check" class="size-5" />
                                        </div>
                                        <div>
                                            <p
                                                class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">
                                                {{ __('Support Resources') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="rounded-xl bg-muted/30 px-3 py-3 text-sm leading-6 text-foreground">
                                        {!! nl2br(e($areasImprovementRecord->support_resources ?? '-')) !!}
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-border bg-card/80 p-4 shadow-sm">
                                    <div class="mb-3 flex items-center gap-2">
                                        <div
                                            class="flex size-10 items-center justify-center rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-400">
                                            <flux:icon icon="chart-bar-square" class="size-5" />
                                        </div>
                                        <div>
                                            <p
                                                class="text-[11px] font-semibold uppercase tracking-widest text-muted-foreground">
                                                {{ __('Progress Intervention') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="rounded-xl bg-muted/30 px-3 py-3 text-sm leading-6 text-foreground">
                                        {!! nl2br(e($areasImprovementRecord->progress_intervention ?? '-')) !!}
                                    </div>
                                </div>
                            </div>

                            <div
                                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-muted/20 px-4 py-3 text-xs text-muted-foreground">
                                <span>{{ __('Latest supervisor feedback entry is shown above.') }}</span>
                                @if (!empty($areasImprovementRecord->encoded_by_name) || !empty($areasImprovementRecord->date_encoded))
                                    <span class="font-medium text-foreground">
                                        {{ trim(($areasImprovementRecord->encoded_by_name ?? '') . (($areasImprovementRecord->date_encoded ?? '') ? ' • ' . \Illuminate\Support\Carbon::parse($areasImprovementRecord->date_encoded)->timezone('Asia/Manila')->format('M d, Y h:i A') : '')) }}
                                    </span>
                                @endif
                            </div>
                        @else
                            <div class="rounded-2xl border border-dashed border-border bg-muted/20 px-4 py-8 text-center">
                                <div
                                    class="mx-auto flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                    <flux:icon icon="information-circle" class="size-6" />
                                </div>
                                <p class="mt-3 text-sm font-semibold text-foreground">
                                    {{ __('No areas of improvement recorded yet.') }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ __('Once supervisor feedback is added, the four cards will appear here.') }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="grid gap-4">
                        <div class="rounded-xl border border-border bg-card/50 p-4">
                            <div class="mb-2 flex items-center gap-3">
                                <div
                                    class="size-9 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                                    <flux:icon icon="chat-bubble-left-right" class="size-4" />
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-foreground">
                                        {{ __("RATER'S COMMENTS, RECOMMENDATIONS AND COMMENDATIONS") }}
                                    </h4>
                                    <p class="text-xs text-muted-foreground">
                                        {{ __('Official remarks from the rater, including recommendations and commendations for performance recognition and improvement.') }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-[10px] ml-12">
                                <div style="margin-left:48px;"
                                    class="prose prose-sm max-w-none text-sm text-foreground dark:prose-invert [&_*]:ml-0 [&_*]:pl-0 [&_p:first-child]:mt-0 [&_p:last-child]:mb-0">
                                    {!! $this->getSemesterRecord()?->recommendation ?? '' !!}
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-border bg-card/50 p-4">
                            <div class="mb-2 flex items-center gap-3">
                                <div
                                    class="size-9 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                    <flux:icon icon="sparkles" class="size-4" />
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-foreground">{{ __('STRENGTHS') }}</h4>
                                    <p class="text-xs text-muted-foreground">
                                        {{ __('Key strengths and positive attributes highlighted during the review period.') }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-[10px] ml-12">
                                <div style="margin-left:48px;"
                                    class="prose prose-sm max-w-none text-sm text-foreground dark:prose-invert [&_*]:ml-0 [&_*]:pl-0 [&_p:first-child]:mt-0 [&_p:last-child]:mb-0">
                                    {!! $this->getSemesterRecord()?->strengths ?? '' !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documentation Tab Content -->
            <div x-show="activeTab === 'documentation'" x-cloak>
                <div class="space-y-6">
                    <div class="flex items-center gap-3">
                        <div
                            class="size-10 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold shrink-0">
                            <flux:icon icon="folder" class="size-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-foreground">
                                {{ __('Semestral Documentation & Attachments') }}
                            </h3>
                            <p class="text-xs text-muted-foreground">
                                {{ __('Supporting documents, guidelines, and reference files for this semester.') }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                        <div class="space-y-4">
                            <div class="rounded-2xl border border-dashed border-border bg-muted/20 p-5" x-data="{
                                    uploading: false,
                                    progress: 0,
                                    start() { this.uploading = true; this.progress = 0; },
                                    finish() { this.uploading = false; this.progress = 100; setTimeout(() => { this.progress = 0; }, 700); },
                                    fail() { this.uploading = false; this.progress = 0; },
                                }" x-on:dragover.prevent="$el.classList.add('border-emerald-500','bg-emerald-500/5')"
                                x-on:dragleave.prevent="$el.classList.remove('border-emerald-500','bg-emerald-500/5')"
                                x-on:drop.prevent="
                                    $el.classList.remove('border-emerald-500','bg-emerald-500/5');
                                    const input = $refs.docUploadInput;
                                    const dt = new DataTransfer();
                                    for (const file of $event.dataTransfer.files) { dt.items.add(file); }
                                    input.files = dt.files;
                                    input.dispatchEvent(new Event('change', { bubbles: true }));
                                ">
                                <div class="flex flex-col items-center justify-center gap-3 text-center">
                                    <div
                                        class="size-14 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                        <flux:icon icon="cloud-arrow-up" class="size-7" />
                                    </div>
                                    <div class="space-y-1">
                                        <h4 class="text-sm font-semibold text-foreground">
                                            {{ __('Drag and drop files here') }}
                                        </h4>
                                        <p class="text-xs text-muted-foreground max-w-xl">
                                            {{ __('Accepted files: PDF, images, PowerPoint presentations, and video files. You can upload multiple files at once.') }}
                                        </p>
                                    </div>
                                    <label for="documentationUploads"
                                        class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                        <flux:icon icon="paper-clip" class="size-4" />
                                        <span>{{ __('Choose Files') }}</span>
                                    </label>
                                    <input id="documentationUploads" x-ref="docUploadInput" type="file" multiple
                                        accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.ppt,.pptx,.mp4,.mov,.avi,.mkv,.wmv,.webm,.m4v"
                                        wire:model="documentationUploads" class="hidden"
                                        x-on:livewire-upload-start="start()" x-on:livewire-upload-finish="finish()"
                                        x-on:livewire-upload-error="fail()" x-on:livewire-upload-cancel="fail()"
                                        x-on:livewire-upload-progress="progress = $event.detail.progress" />
                                    <flux:error name="documentationUploads" />

                                    <div x-cloak x-show="uploading" class="w-full max-w-xl space-y-2 pt-2">
                                        <div
                                            class="flex items-center justify-between text-[11px] text-muted-foreground">
                                            <span>{{ __('Uploading files...') }}</span>
                                            <span x-text="`${progress}%`"></span>
                                        </div>
                                        <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-zinc-700">
                                            <div class="h-full rounded-full bg-emerald-600 transition-[width] duration-150"
                                                :style="`width: ${progress}%`"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="grid grid-cols-2 gap-2 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-8 xl:grid-cols-10">
                                @forelse ($this->documentationFiles as $file)
                                    <button type="button" wire:click="openDocumentationPreview(@js($file['name']))"
                                        class="group w-full min-w-0 overflow-hidden rounded-md border border-border bg-card text-left shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-500/60 hover:shadow-md">
                                        <div
                                            class="relative aspect-square w-full overflow-hidden bg-slate-100 dark:bg-zinc-800">
                                            @if ($file['type'] === 'image')
                                                <img src="{{ $file['url'] }}" alt="{{ $file['name'] }}"
                                                    class="h-full w-full object-cover">
                                            @elseif ($file['type'] === 'pdf')
                                                <div
                                                    class="flex h-full w-full items-center justify-center bg-red-50 text-red-600 dark:bg-red-950/30 dark:text-red-300">
                                                    <flux:icon icon="document-text" class="size-4" />
                                                </div>
                                            @elseif ($file['type'] === 'presentation')
                                                <div
                                                    class="flex h-full w-full items-center justify-center bg-orange-50 text-orange-600 dark:bg-orange-950/30 dark:text-orange-300">
                                                    <flux:icon icon="presentation-chart-bar" class="size-4" />
                                                </div>
                                            @elseif ($file['type'] === 'video')
                                                <div
                                                    class="flex h-full w-full items-center justify-center bg-slate-900 text-white">
                                                    <flux:icon icon="play" class="size-4" />
                                                </div>
                                            @else
                                                <div
                                                    class="flex h-full w-full items-center justify-center bg-indigo-50 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-300">
                                                    <flux:icon icon="document" class="size-4" />
                                                </div>
                                            @endif
                                            <div
                                                class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-2 pb-1 pt-5">
                                                <p class="truncate text-[10px] font-semibold text-white">
                                                    {{ $file['name'] }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="space-y-0 p-1">
                                            <div class="truncate text-[8px] font-medium leading-[10px] text-foreground">
                                                {{ $file['name'] }}
                                            </div>
                                            <div class="mt-0.5 truncate text-[7px] leading-2 text-muted-foreground">
                                                {{ $file['modified_at'] }}
                                            </div>
                                        </div>
                                    </button>
                                @empty
                                    <div
                                        class="col-span-full flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-muted/20 py-12 text-center">
                                        <div
                                            class="size-12 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                            <flux:icon icon="folder" class="size-6" />
                                        </div>
                                        <h4 class="mt-3 text-sm font-semibold text-foreground">
                                            {{ __('No Documentation Uploaded') }}
                                        </h4>
                                        <p class="mt-1 text-xs text-muted-foreground max-w-sm">
                                            {{ __('Additional semestral resources, MOVs summaries, and reference documentation will appear here.') }}
                                        </p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($showDocumentationPreviewModal && $previewDocumentationFile)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/75 p-4"
            wire:keydown.escape.window="closeDocumentationPreview">
            <div
                class="flex h-[700px] max-h-[90vh] w-[95vw] flex-col overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl sm:w-[70vw]">
                <div class="flex items-center justify-between border-b border-slate-700 px-4 py-3">
                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-semibold text-white">
                            {{ $previewDocumentationFile['name'] }}
                        </h3>
                        <p class="text-xs text-slate-400">{{ $previewDocumentationFile['modified_at'] }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:button size="sm" variant="danger" wire:click="deleteDocumentationFile"
                            wire:confirm="{{ __('Delete this documentation file from storage?') }}">
                            {{ __('Remove') }}
                        </flux:button>
                        <flux:button size="sm" variant="ghost" class="text-white hover:bg-white/10"
                            wire:click="closeDocumentationPreview">
                            {{ __('Close') }}
                        </flux:button>
                    </div>
                </div>
                <div class="min-h-0 flex-1 bg-black">
                    @if ($previewDocumentationFile['type'] === 'image')
                        <img src="{{ $previewDocumentationFile['url'] }}" alt="{{ $previewDocumentationFile['name'] }}"
                            class="h-full w-full object-contain">
                    @elseif (in_array($previewDocumentationFile['type'], ['pdf', 'presentation', 'word'], true))
                        @php
                            $viewerUrl = $previewDocumentationFile['type'] === 'pdf'
                                ? $previewDocumentationFile['url']
                                : 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode($previewDocumentationFile['url']);
                        @endphp
                        <iframe src="{{ $viewerUrl }}" class="h-full w-full"
                            title="{{ $previewDocumentationFile['name'] }}"></iframe>
                    @elseif ($previewDocumentationFile['type'] === 'video')
                        <video controls class="h-full w-full bg-black object-contain">
                            <source src="{{ $previewDocumentationFile['url'] }}">
                        </video>
                    @else
                        <div class="flex h-full items-center justify-center text-center text-white">
                            <div>
                                <flux:icon icon="document" class="mx-auto size-12 text-slate-300" />
                                <p class="mt-3 text-sm text-slate-300">{{ __('Preview is not available for this file type.') }}
                                </p>
                                <a href="{{ $previewDocumentationFile['url'] }}" target="_blank"
                                    class="mt-4 inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">
                                    {{ __('Open File') }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Add Target Modal -->
    @if ($showAddModal)
        <flux:modal wire:model="showAddModal"
            style="width: min(72rem, calc(100vw - 2rem)); max-width: min(72rem, calc(100vw - 2rem));">
            <div class="space-y-5">
                <div class="space-y-1">
                    <flux:heading size="lg">{{ __('Add Target') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Create a new semestral target entry inside the selected KRA category.') }}
                    </flux:subheading>
                </div>

                <table class="w-full table-fixed border-collapse" style="border: 0;">
                    <tbody>
                        <tr>
                            <td class="w-1/2 align-top pe-3" style="border: 0;">
                                <div class="grid gap-1">
                                    <flux:label>{{ __('KRA Category') }}</flux:label>
                                    <div>
                                        <flux:badge color="blue">
                                            {{ \App\Support\KraCategory::label($addingKraCategory ?? 1) }}
                                        </flux:badge>
                                    </div>
                                </div>
                            </td>
                            <td class="w-1/2 align-top ps-3" style="border: 0;">
                                <div class="grid gap-1">
                                    <flux:label>{{ __('Semester Period') }}</flux:label>
                                    <div>
                                        <flux:badge color="zinc">
                                            {{ $this->semesterHeading() }}
                                        </flux:badge>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="grid items-start gap-4 md:grid-cols-2">
                    <div class="grid gap-1">
                        <flux:label>{{ __('Key Result Area') }} <span class="text-red-500">*</span></flux:label>
                        <textarea data-autosize="true" wire:model="addActivity" rows="1"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                            style="resize:none;"></textarea>
                        <flux:error name="addActivity" />
                    </div>

                    <div class="grid gap-1">
                        <flux:label>{{ __('Success Indicator') }} <span class="text-red-500">*</span></flux:label>
                        <textarea data-autosize="true" wire:model="addDescription" rows="1"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                            style="resize:none;"></textarea>
                        <flux:error name="addDescription" />
                    </div>
                </div>

                <div class="flex items-center gap-3" role="separator" aria-label="{{ __('Rating Guide') }}">
                    <div class="h-px flex-1 bg-border"></div>
                    <span class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                        {{ __('Rating Guide') }}
                    </span>
                    <div class="h-px flex-1 bg-border"></div>
                </div>

                <div class="grid items-start gap-4 md:grid-cols-2">
                    <div class="grid gap-1">
                        <flux:label>{{ __('Efficiency') }} <span class="text-red-500">*</span></flux:label>
                        <textarea data-autosize="true" wire:model="addEfficiency" rows="1"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                            style="resize:none;"></textarea>
                        <flux:error name="addEfficiency" />
                    </div>

                    <div class="grid gap-1">
                        <flux:label>{{ __('Quality') }} <span class="text-red-500">*</span></flux:label>
                        <textarea data-autosize="true" wire:model="addQuality" rows="1"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                            style="resize:none;"></textarea>
                        <flux:error name="addQuality" />
                    </div>

                    <div class="grid gap-1">
                        <flux:label>{{ __('Timeliness') }} <span class="text-red-500">*</span></flux:label>
                        <textarea data-autosize="true" wire:model="addTimeliness" rows="1"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                            style="resize:none;"></textarea>
                        <flux:error name="addTimeliness" />
                    </div>

                    <div class="grid gap-1">
                        <flux:label>{{ __('MOVs') }} <span class="text-red-500">*</span></flux:label>
                        <textarea data-autosize="true" wire:model="addMovs" rows="1"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                            style="resize:none;"></textarea>
                        <flux:error name="addMovs" />
                    </div>

                    <div class="grid gap-1 md:col-span-2" style="grid-column: 1 / -1;">
                        <flux:label>{{ __('Remarks') }} <span
                                class="text-xs text-muted-foreground">({{ __('Optional') }})</span></flux:label>
                        <textarea data-autosize="true" wire:model="addRemarks" rows="1"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                            style="resize:none;"></textarea>
                        <flux:error name="addRemarks" />
                    </div>

                    <div class="grid gap-1 md:col-span-2" style="grid-column: 1 / -1;">
                        <flux:label>{{ __('Justification') }} @if($this->is2026SecondSemesterOrBeyond()) <span
                        class="text-red-500">*</span> @else <span
                                class="text-xs text-muted-foreground">({{ __('Optional') }})</span> @endif</flux:label>
                        <textarea data-autosize="true" wire:model="addJustification" rows="2"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                            placeholder="{{ __('Enter justification for adding this target...') }}"
                            style="resize:none;"></textarea>
                        <flux:error name="addJustification" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">
                            {{ __('Cancel') }}
                        </flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="button" class="bg-emerald-600 text-white hover:bg-emerald-700"
                        wire:click="saveAdd">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Delete Target Modal -->
    @if ($showDeleteModal)
        <flux:modal wire:model="showDeleteModal" dismissible>
            <div class="space-y-4">
                <div class="space-y-1">
                    <flux:heading size="lg">{{ __('Delete Target Entry') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Are you sure you want to delete this semestral target entry? It can be recovered later using the Recover Deleted Targets menu.') }}
                    </flux:subheading>
                </div>

                <div class="grid gap-1">
                    <flux:label>{{ __('Justification') }} @if($this->is2026SecondSemesterOrBeyond()) <span
                    class="text-red-500">*</span> @else <span
                            class="text-xs text-muted-foreground">({{ __('Optional') }})</span> @endif</flux:label>
                    <textarea data-autosize="true" wire:model="deleteJustification" rows="2"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        placeholder="{{ __('Enter justification for deleting this target...') }}"
                        style="resize:none;"></textarea>
                    <flux:error name="deleteJustification" />
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" type="button" wire:click="cancelDeleteTarget">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="danger" wire:click="confirmDeleteTarget">
                        {{ __('Delete') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Recover Deleted Targets Modal -->
    @if ($showRecoverModal)
        <flux:modal wire:model="showRecoverModal"
            style="width: min(66rem, calc(100vw - 2rem)); max-width: min(66rem, calc(100vw - 2rem));">
            <div class="space-y-5">
                <div class="space-y-1">
                    <flux:heading size="lg">{{ __('Recover Deleted Targets') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Review and restore targets that were previously deleted from your semestral target list.') }}
                    </flux:subheading>
                </div>

                <div class="max-h-[60vh] overflow-y-auto rounded-xl border border-border">
                    <table class="w-full border-collapse text-xs">
                        <thead
                            class="sticky top-0 bg-muted/90 backdrop-blur-md text-left font-semibold uppercase text-muted-foreground border-b border-border">
                            <tr>
                                <th class="border-r border-border px-3 py-2.5">{{ __('KRA Category') }}</th>
                                <th class="border-r border-border px-3 py-2.5">{{ __('Key Result Area / Activity') }}</th>
                                <th class="border-r border-border px-3 py-2.5">{{ __('Deleted Date & User') }}</th>
                                <th class="border-r border-border px-3 py-2.5">{{ __('Justification') }}</th>
                                <th class="px-3 py-2.5 text-center">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($deletedTargetsList as $item)
                                <tr class="border-b border-border/60 hover:bg-muted/20">
                                    <td
                                        class="border-r border-border px-3 py-2 align-top font-semibold text-slate-800 dark:text-zinc-200">
                                        {{ $item['kra_category_label'] }}
                                    </td>
                                    <td class="border-r border-border px-3 py-2 align-top">
                                        <div class="font-bold text-slate-900 dark:text-zinc-100">{{ $item['activity'] }}</div>
                                        @if (!empty($item['description']))
                                            <div class="text-[11px] text-muted-foreground mt-1">
                                                {!! nl2br(e($item['description'])) !!}
                                            </div>
                                        @endif
                                    </td>
                                    <td
                                        class="border-r border-border px-3 py-2 align-top text-muted-foreground whitespace-nowrap">
                                        <div>{{ $item['deleted_at'] }}</div>
                                        <div class="text-[10px] font-semibold text-slate-500">{{ $item['user_name'] }}</div>
                                    </td>
                                    <td class="border-r border-border px-3 py-2 align-top italic text-foreground">
                                        {!! nl2br(e($item['justification'])) !!}
                                    </td>
                                    <td class="px-3 py-2 align-top text-center">
                                        <flux:button size="xs" variant="primary"
                                            class="bg-emerald-600 text-white hover:bg-emerald-700 font-semibold"
                                            wire:click="recoverTarget({{ $item['sem_target_id'] }})">
                                            {{ __('Restore') }}
                                        </flux:button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-muted-foreground">
                                        {{ __('No deleted targets found for recovery.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">
                            {{ __('Close') }}
                        </flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Delete Sub-Target Modal -->
    @if ($showDeleteSubTargetModal)
        <flux:modal wire:model="showDeleteSubTargetModal" dismissible>
            <div class="space-y-4">
                <div class="space-y-1">
                    <flux:heading size="lg">{{ __('Delete Sub-Target Entry') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Are you sure you want to delete this sub-target item? It can be recovered later using the Recover Deleted Targets menu.') }}
                    </flux:subheading>
                </div>

                <div class="grid gap-1">
                    <flux:label>{{ __('Justification') }} @if($this->is2026SecondSemesterOrBeyond()) <span
                    class="text-red-500">*</span> @else <span
                            class="text-xs text-muted-foreground">({{ __('Optional') }})</span> @endif</flux:label>
                    <textarea data-autosize="true" wire:model="deleteJustification" rows="2"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        placeholder="{{ __('Enter justification for deleting this sub-target...') }}"
                        style="resize:none;"></textarea>
                    <flux:error name="deleteJustification" />
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" type="button" wire:click="cancelDeleteSubTarget">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="danger" wire:click="confirmDeleteSubTarget">
                        {{ __('Delete') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Reordering / Sorting Global Loading Overlay -->
    <div wire:loading wire:target="targetDropped"
        class="fixed inset-0 z-50 flex items-center justify-center bg-background/50 backdrop-blur-xs">
        <div class="flex items-center gap-3 rounded-2xl bg-card px-6 py-4 shadow-xl border border-border">
            <svg class="animate-spin size-6 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <span class="text-sm font-bold text-foreground">{{ __('Updating target positions...') }}</span>
        </div>
    </div>

    <!-- Copy Target Modal -->
    @if ($showCopyModal)
        <flux:modal wire:model="showCopyModal" dismissible style="width: 80%; max-width: 80%; height: 90%; max-height: 90%;"
            class="overflow-y-auto">
            @include('livewire.semestral-target.copy-target-modal')
        </flux:modal>
    @endif

    <!-- Confirm Copy All Modal -->
    @if ($showCopyAllConfirmModal)
        <flux:modal wire:model="showCopyAllConfirmModal" dismissible>
            <div class="space-y-5">
                <div class="space-y-1">
                    <flux:heading size="lg">{{ __('Copy all filtered targets?') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Are you sure you want to copy all filtered target results to your semestral target list?') }}
                    </flux:subheading>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">
                            {{ __('Cancel') }}
                        </flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="button" class="bg-emerald-600 text-white hover:bg-emerald-700"
                        wire:click="confirmCopyAll">
                        {{ __('Confirm and Copy') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Show Edit History Modal -->
    @if ($showHistoryModal)
        <flux:modal wire:model="showHistoryModal"
            style="width: min(66rem, calc(100vw - 2rem)); max-width: min(66rem, calc(100vw - 2rem));">
            <div class="space-y-5">
                <div class="space-y-1">
                    <flux:heading size="lg">{{ __('Edit History') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Review all modifications, edits, and justifications recorded for the selected target.') }}
                    </flux:subheading>
                </div>

                <div class="max-h-[60vh] overflow-y-auto rounded-xl border border-border">
                    @php
                        $formatHistoryValue = static function (mixed $value): string {
                            if ($value === null || $value === '') {
                                return '-';
                            }
                            $text = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
                            $text = str_replace(["\r\n", "\r"], "\n", $text ?? '-');

                            return trim($text) === '' ? '-' : $text;
                        };
                    @endphp
                    <table class="w-full border-collapse text-xs">
                        <thead
                            class="sticky top-0 bg-muted/90 backdrop-blur-md text-left font-semibold uppercase text-muted-foreground border-b border-border">
                            <tr>
                                <th class="border-r border-border px-3 py-2.5">{{ __('Field / Type') }}</th>
                                <th class="border-r border-border px-3 py-2.5">{{ __('Original / Old Value') }}</th>
                                <th class="border-r border-border px-3 py-2.5">{{ __('New Value') }}</th>
                                <th class="border-r border-border px-3 py-2.5">{{ __('Date & User') }}</th>
                                <th class="px-3 py-2.5">{{ __('Justification') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($historyRecords as $history)
                                @if ($history['is_separator'] ?? false)
                                    <tr class="bg-muted/50 border-y border-border text-muted-foreground text-[11px] font-semibold">
                                        <td colspan="3"
                                            class="border-r border-border px-3 py-1.5 text-center tracking-wide uppercase bg-slate-100 dark:bg-zinc-800/70 text-slate-600 dark:text-zinc-300 align-top max-w-0">
                                            <div class="truncate max-w-full"
                                                title="{{ '--- ' . ($history['separator_title'] ?? __('Group')) . ' ---' }}">
                                                {{ '--- ' . ($history['separator_title'] ?? __('Group')) . ' ---' }}
                                            </div>
                                        </td>
                                        <td
                                            class="border-r border-border px-3 py-2 align-top text-muted-foreground whitespace-nowrap bg-background">
                                            <div>{{ $history['date_created'] }}</div>
                                            <div class="text-[10px] font-semibold text-slate-500">{{ $history['user_name'] }}</div>
                                        </td>
                                        @if (($history['justification_rowspan'] ?? 1) > 0)
                                            <td @if(($history['justification_rowspan'] ?? 1) > 1)
                                            rowspan="{{ $history['justification_rowspan'] }}" @endif
                                                class="px-3 py-2 align-top text-foreground italic bg-background">
                                                {!! nl2br(e($history['justification'])) !!}
                                            </td>
                                        @endif
                                    </tr>
                                @else
                                    <tr class="border-b border-border/60 hover:bg-muted/20">
                                        <td
                                            class="border-r border-border px-3 py-2 align-top font-semibold text-slate-800 dark:text-zinc-200 uppercase">
                                            {{ $history['field_label'] ?? str_replace('_', ' ', $history['field_name']) }}
                                        </td>
                                        <td
                                            class="border-r border-border px-3 py-2 align-top text-muted-foreground whitespace-pre-line">
                                            {!! nl2br(e($formatHistoryValue($history['old_value'] ?: ($history['original_value'] ?: '-')))) !!}
                                        </td>
                                        <td
                                            class="border-r border-border px-3 py-2 align-top font-medium text-emerald-600 dark:text-emerald-400 whitespace-pre-line">
                                            {!! nl2br(e($formatHistoryValue($history['new_value'] ?: '-'))) !!}
                                        </td>
                                        <td
                                            class="border-r border-border px-3 py-2 align-top text-muted-foreground whitespace-nowrap">
                                            <div>{{ $history['date_created'] }}</div>
                                            <div class="text-[10px] font-semibold text-slate-500">{{ $history['user_name'] }}</div>
                                        </td>
                                        @if (($history['justification_rowspan'] ?? 1) > 0)
                                            <td @if(($history['justification_rowspan'] ?? 1) > 1)
                                            rowspan="{{ $history['justification_rowspan'] }}" @endif
                                                class="px-3 py-2 align-top text-foreground italic">
                                                {!! nl2br(e($history['justification'])) !!}
                                            </td>
                                        @endif
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-muted-foreground">
                                        {{ __('No edit history records found for this target entry.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between gap-2 pt-2">
                    @if (!$this->isHistoryTargetLocked())
                        <flux:button variant="danger" type="button" icon="trash" wire:click="discardEditHistory"
                            :disabled="empty($historyRecords)"
                            class="bg-red-600 text-white hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700">
                            {{ __('Discard') }}
                        </flux:button>
                    @endif

                    <flux:modal.close>
                        <flux:button variant="ghost" type="button" wire:click="closeEditHistoryModal">
                            {{ __('Close') }}
                        </flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Save and Lock Confirm Modal -->
    @if ($showLockConfirmModal)
        <flux:modal wire:model="showLockConfirmModal" dismissible>
            <div class="space-y-4">
                <div class="space-y-1">
                    <flux:heading size="lg">{{ __('Save and Lock Semestral Target') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Are you sure you want to save and lock all targets for this semester? Once locked, targets cannot be edited, deleted, or modified via right-click.') }}
                    </flux:subheading>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button variant="ghost" type="button" wire:click="cancelLockConfirm">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="button"
                        class="bg-amber-600 text-white hover:bg-amber-700 font-semibold"
                        wire:click="saveAndLockSemestralTarget">
                        {{ __('Confirm and Lock') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Unlock Confirm Modal -->
    @if ($showUnlockConfirmModal)
        <flux:modal wire:model="showUnlockConfirmModal" dismissible>
            <div class="space-y-4">
                <div class="space-y-1">
                    <flux:heading size="lg">{{ __('Unlock Semestral Target') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Are you sure you want to unlock all targets for this semester? Once unlocked, targets can be edited, deleted, or modified via right-click.') }}
                    </flux:subheading>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button variant="ghost" type="button" wire:click="cancelUnlockConfirm">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="button"
                        class="bg-emerald-600 text-white hover:bg-emerald-700 font-semibold"
                        wire:click="saveAndUnlockSemestralTarget">
                        {{ __('Confirm and Unlock') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- I'm Ready Confirm Modal -->
    @if ($showImReadyConfirmModal)
        <flux:modal wire:model="showImReadyConfirmModal" dismissible>
            <div class="space-y-4">
                <div class="space-y-1">
                    <flux:heading size="lg">{{ __("Confirm Ready Submission") }}</flux:heading>
                    <flux:subheading>
                        {{ __("Are you sure you are ready to submit your semestral target ratings? Once confirmed, your targets and scores will be locked (ipc_semester.lock = 2) and can no longer be edited.") }}
                    </flux:subheading>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button variant="ghost" type="button" wire:click="cancelImReadyConfirm">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="button"
                        class="bg-emerald-600 text-white hover:bg-emerald-700 font-semibold" wire:click="confirmImReady">
                        {{ __("Yes, I'm Ready") }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    <!-- Waiting for Verification Confirm Modal -->
    @if ($showWaitingVerificationModal)
        <flux:modal wire:model="showWaitingVerificationModal" dismissible>
            <div class="space-y-4">
                <div class="space-y-1">
                    <flux:heading size="lg">{{ __('Unlock Target for Editing') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Are you sure you want to revert status back to rating mode? This will allow you to edit target scores, accomplishments, MOVs, and remarks again.') }}
                    </flux:subheading>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button variant="ghost" type="button" wire:click="cancelWaitingVerificationConfirm">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="button"
                        class="bg-amber-600 text-white hover:bg-amber-700 font-semibold"
                        wire:click="confirmWaitingVerification">
                        {{ __('Confirm and Unlock') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

</section>
