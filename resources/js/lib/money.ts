export function formatMoney(amount: number, currency: string): string {
    try {
        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency,
            maximumFractionDigits: amount >= 100 ? 0 : 2,
        }).format(amount);
    } catch {
        return `${currency} ${amount.toFixed(2)}`;
    }
}
