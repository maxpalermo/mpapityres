class DateFormatter {
    static toItalian(dateISO) {
        if (!dateISO) return "";

        const date = new Date(dateISO);
        if (isNaN(date.getTime())) return dateISO;

        const day = date.getDate().toString().padStart(2, "0");
        const month = (date.getMonth() + 1).toString().padStart(2, "0");
        const year = date.getFullYear();

        return `${day}/${month}/${year}`;
    }

    static toItalianWithTime(dateISO) {
        if (!dateISO) return "";

        const date = new Date(dateISO);
        if (isNaN(date.getTime())) return dateISO;

        const day = date.getDate().toString().padStart(2, "0");
        const month = (date.getMonth() + 1).toString().padStart(2, "0");
        const year = date.getFullYear();
        const hours = date.getHours().toString().padStart(2, "0");
        const minutes = date.getMinutes().toString().padStart(2, "0");

        return `${day}/${month}/${year} ${hours}:${minutes}`;
    }

    static toItalianLong(dateISO) {
        if (!dateISO) return "";

        const date = new Date(dateISO);
        if (isNaN(date.getTime())) return dateISO;

        return date.toLocaleDateString("it-IT", {
            weekday: "long",
            day: "numeric",
            month: "long",
            year: "numeric",
        });
    }
}
