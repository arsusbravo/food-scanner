/**
 * Downscales a photo to a JPEG blob. Receipts need more resolution than food
 * photos so small print stays readable.
 */
export function compressImage(file: File, maxPx = 2200, quality = 0.9): Promise<Blob> {
    return new Promise((resolve) => {
        const img = new Image();

        img.onload = () => {
            const scale = Math.min(1, maxPx / Math.max(img.width, img.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(img.width * scale);
            canvas.height = Math.round(img.height * scale);
            canvas.getContext('2d')!.drawImage(img, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => resolve(blob ?? file), 'image/jpeg', quality);
        };

        img.onerror = () => resolve(file);
        img.src = URL.createObjectURL(file);
    });
}

/** Returns the base64 payload (without the data: prefix) of a blob. */
export function blobToBase64(blob: Blob): Promise<string> {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onload = (e) => resolve((e.target!.result as string).split(',')[1]);
        reader.onerror = reject;
        reader.readAsDataURL(blob);
    });
}
