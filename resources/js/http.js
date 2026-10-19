export async function fetchJson(url, options = {}) {
    const { headers: extraHeaders, ...rest } = options;
    const response = await fetch(url, {
        credentials: 'same-origin',
        ...rest,
        headers: {
            Accept: 'application/json',
            ...(extraHeaders ?? {}),
        },
    });

    if (!response.ok) {
        let message = 'Something went wrong. Please try again.';

        try {
            const body = await response.json();

            if (typeof body?.message === 'string' && body.message !== '') {
                message = body.message;
            }
        } catch {
            // Keep the generic message when the body is not JSON.
        }

        const error = new Error(message);
        error.status = response.status;
        throw error;
    }

    const contentType = response.headers.get('content-type') ?? '';

    if (!contentType.includes('application/json')) {
        return null;
    }

    return response.json();
}
