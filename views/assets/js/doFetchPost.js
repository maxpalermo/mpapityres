async function doFetchPost($url, $params) {
    let response;

    $params.ajax = 1;

    try {
        response = await fetch($url, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams($params),
        });
    } catch (error) {
        fetchResponseCode = 500;
        return error;
    }

    fetchResponseCode = response.status;
    if (fetchResponseCode != 200) {
        return response;
    }

    const data = await response.json();

    return data;
}
