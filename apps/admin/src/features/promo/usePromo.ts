import { useMutation } from "@tanstack/react-query";
import { api } from "@/lib/api";

/**
 * Stores the "no thanks" for the FormFlow cross-promotion. The flag is shared
 * with the Dashboard notice, so one dismissal retires both surfaces.
 */
export function useDismissPromo() {
    return useMutation<{ ok: boolean }, Error, void>({
        mutationFn: () => api.post<{ ok: boolean }>("/promo/dismiss"),
    });
}
