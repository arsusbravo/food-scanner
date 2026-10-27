import { ref, watch } from 'vue';

type CostForm = {
    item_name: string;
    weight_kg: number | '';
    cost: number | '';
};

/**
 * Pre-fills the cost field from the price per kg the user last paid for the
 * same item. Stops suggesting once the user edits the cost themselves.
 */
export function useCostSuggestion(form: CostForm, itemPrices: Record<string, number>) {
    const suggested = ref(false);
    let edited = false;

    watch(
        () => [form.item_name, form.weight_kg] as const,
        ([name, weight]) => {
            if (edited) return;

            const pricePerKg = itemPrices[name.trim().toLowerCase()];

            if (pricePerKg !== undefined && weight !== '' && Number(weight) > 0) {
                form.cost = Math.round(pricePerKg * Number(weight) * 100) / 100;
                suggested.value = true;
            } else if (suggested.value) {
                form.cost = '';
                suggested.value = false;
            }
        },
    );

    function markEdited() {
        edited = true;
        suggested.value = false;
    }

    function reset() {
        edited = false;
        suggested.value = false;
    }

    return { suggested, markEdited, reset };
}
