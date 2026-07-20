<script setup lang="ts">
import NumberStepper from '@/components/NumberStepper.vue';
import { formatPriceCents } from '@/lib/property';
import { useTranslations } from '@/lib/trans';
import { computed, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
    price: number; // cents
}>();

const { t, locale } = useTranslations();

const priceEuros = props.price / 100;

const downPaymentStep = Math.max(1, Math.round(priceEuros * 0.01));

const downPayment = ref(Math.round(priceEuros * 0.2));
const loanYears = ref(10);
const monthlyIncome = ref(1000);
const interestRate = ref(5);

// Recalculation is debounced so the +/- steppers stay snappy — the buttons
// themselves respond instantly, only the derived figures below lag by 150ms.
const debounced = ref({
    downPayment: downPayment.value,
    loanYears: loanYears.value,
    monthlyIncome: monthlyIncome.value,
    interestRate: interestRate.value,
});

let debounceTimer: ReturnType<typeof setTimeout> | undefined;

watch([downPayment, loanYears, monthlyIncome, interestRate], ([dp, ly, mi, ir]) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        debounced.value = { downPayment: dp, loanYears: ly, monthlyIncome: mi, interestRate: ir };
    }, 150);
});

onUnmounted(() => clearTimeout(debounceTimer));

const loanAmount = computed(() => Math.max(0, priceEuros - debounced.value.downPayment));

const downPaymentPercent = computed(() => (priceEuros === 0 ? 0 : (debounced.value.downPayment / priceEuros) * 100));

const monthlyInstalment = computed(() => {
    const amount = loanAmount.value;
    const months = debounced.value.loanYears * 12;
    const monthlyRate = debounced.value.interestRate / 100 / 12;

    if (amount <= 0) {
        return 0;
    }

    // Standard annuity formula degenerates to a division by zero at 0% interest.
    return monthlyRate === 0 ? amount / months : (amount * monthlyRate) / (1 - Math.pow(1 + monthlyRate, -months));
});

const debtBurden = computed(() => (debounced.value.monthlyIncome > 0 ? monthlyInstalment.value / debounced.value.monthlyIncome : 0));

type RiskLevel = 'low' | 'moderate' | 'high';

const riskLevel = computed<RiskLevel>(() => {
    if (debtBurden.value < 0.3) return 'low';
    if (debtBurden.value <= 0.45) return 'moderate';
    return 'high';
});

const riskClasses: Record<RiskLevel, string> = {
    low: 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
    moderate: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    high: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
};

// Computed, not a plain const: switching locale is a client-side Inertia
// visit that reuses this component instance, so a one-off t() call baked
// into a plain object would freeze at whatever locale first mounted it.
const riskLabels = computed<Record<RiskLevel, string>>(() => ({
    low: t('Rrezik i ulët'),
    moderate: t('Rrezik mesatar'),
    high: t('Rrezik i lartë — shumica e bankave mund të mos e miratojnë'),
}));

function formatEuro(euros: number): string {
    return formatPriceCents(Math.round(euros * 100), locale.value);
}

function formatYears(value: number): string {
    return `${value} ${t('vjet')}`;
}

function formatPercent(value: number): string {
    return `${value.toFixed(1)}%`;
}
</script>

<template>
    <div class="rounded-xl border bg-card p-4">
        <h2 class="font-medium">{{ t('Kalkulatori i Financimit') }}</h2>

        <div class="mt-4 space-y-4">
            <NumberStepper
                id="down-payment"
                v-model="downPayment"
                :min="0"
                :max="priceEuros"
                :step="downPaymentStep"
                :label="t('Parapagimi')"
                :format="formatEuro"
            />
            <NumberStepper
                id="loan-years"
                v-model="loanYears"
                :min="1"
                :max="30"
                :step="1"
                :label="t('Kohëzgjatja e kredisë')"
                :format="formatYears"
            />
            <NumberStepper
                id="monthly-income"
                v-model="monthlyIncome"
                :min="100"
                :max="1000000"
                :step="50"
                :label="t('Të ardhurat mujore')"
                :format="formatEuro"
            />
            <NumberStepper
                id="interest-rate"
                v-model="interestRate"
                :min="0"
                :max="20"
                :step="0.5"
                :label="t('Norma vjetore e interesit')"
                :format="formatPercent"
            />
        </div>

        <div class="mt-5 space-y-2 border-t pt-4 text-sm">
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">{{ t('Shuma e kredisë') }}</span>
                <span class="font-medium">{{ formatEuro(loanAmount) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">{{ t('Parapagimi (% e çmimit)') }}</span>
                <span class="font-medium">{{ formatPercent(downPaymentPercent) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground">{{ t('Kësti mujor') }}</span>
                <span class="text-base font-medium">
                    {{ formatEuro(monthlyInstalment) }}<span class="text-xs font-normal text-muted-foreground">{{ t('/muaj') }}</span>
                </span>
            </div>
            <div class="flex items-center justify-between gap-2">
                <span class="text-muted-foreground">{{ t('Ngarkesa e borxhit') }}</span>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="riskClasses[riskLevel]">
                    {{ formatPercent(debtBurden * 100) }} · {{ riskLabels[riskLevel] }}
                </span>
            </div>
        </div>

        <p class="mt-4 text-xs text-muted-foreground">
            {{ t('Ky është vetëm një vlerësim informativ dhe nuk përbën ofertë financimi. Për kushte të sakta, kontaktoni bankën tuaj.') }}
        </p>
    </div>
</template>
