class FetchManager
{
    endpoint = null;
    method = 'POST';
    payload = null;
    error = null;

    constructor(endpoint, payload = null, method = 'POST') {
        this.endpoint = endpoint;
        this.method = method;
        this.payload = payload;
    }

    getError()
    {
        return this.error;
    }

    async POST()
    {
        const self = this;
        try {
            const response = await fetch(self.endpoint, {
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                method: 'POST',
                body: new URLSearchParams(self.payload)
            });

            const json = await response.json();
    
            return json;
        } catch (error) {
            self.error = error;
            return false;
        }
    }

    async GET()
    {
        const self = this;
        let endpoint = self.addParamsToUrl(self.endpoint, self.payload);

        try {
            const response = await fetch(endpoint);

            const json = await response.json();
    
            return json;
        } catch (error) {
            self.error = error;
            return false;
        }
    }

    addParamsToUrl(url, params) {
    // Converte l'oggetto in stringa parametri
    const queryString = new URLSearchParams(params).toString();
    
    if (!queryString) return url; // Se non ci sono parametri, ritorna l'URL originale
    
    // Controlla se l'URL ha già parametri
    const hasExistingParams = url.includes('?');
    const separator = hasExistingParams ? '&' : '?';
    
    return url + separator + queryString;
}
}