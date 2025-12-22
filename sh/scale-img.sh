#!/bin/bash

# Dimensioni di default
DEFAULT_SIZE="400x400"
SIZE="$DEFAULT_SIZE"

# Controlla i parametri
if [ -z "$1" ]; then
    echo "Usage: $0 <directory> [dimensioni]"
    echo "Esempio: $0 /var/www/html/img"
    echo "Esempio: $0 /var/www/html/img 600x800"
    echo "Esempio: $0 /var/www/html/img 300x300"
    echo "Dimensioni di default: ${DEFAULT_SIZE}"
    exit 1
fi

BASE_DIR="$1"

# Se è stato fornito il secondo parametro, usa quelle dimensioni
if [ -n "$2" ]; then
    # Verifica il formato delle dimensioni (es. 400x400, 600x800, ecc.)
    if [[ ! "$2" =~ ^[0-9]+x[0-9]+$ ]]; then
        echo "Errore: Formato dimensioni non valido. Usa il formato: LARGHEZZAxALTEZZA"
        echo "Esempio: 400x400, 600x800, 300x300"
        exit 1
    fi
    SIZE="$2"
fi

# Controlla se la directory esiste
if [ ! -d "$BASE_DIR" ]; then
    echo "Errore: La directory '$BASE_DIR' non esiste"
    exit 1
fi

# Controlla se ImageMagick è installato (controllo semplificato)
if ! which convert >/dev/null; then
    echo "ImageMagick non è installato. Installalo con:"
    echo "sudo apt-get install imagemagick"
    exit 1
fi

echo "=== Impostazioni ==="
echo "Directory: $BASE_DIR"
echo "Dimensioni: ${SIZE}px"
echo ""

# Contatori per le statistiche
count_elaborati=0
count_saltati=0

# Trova tutti i file .jpg (case insensitive) ricorsivamente
find "$BASE_DIR" -type f -iname "*.jpg" | while read -r image_file; do
    # Estrai il nome del file senza percorso
    filename=$(basename "$image_file")
    
    # Controlla se il nome del file corrisponde al pattern <codice>.jpg
    # (solo numeri, non contiene parole come -small, -medium, etc.)
    if [[ "$filename" =~ ^[0-9]+\.jpg$ ]]; then
        echo "Elaboro: $image_file"
        
        # Crea un file temporaneo per sicurezza
        temp_file="${image_file}.tmp"
        
        # Usa convert per ridimensionare mantenendo le proporzioni
        # -resize ${SIZE}> ridimensiona solo se l'immagine è più grande delle dimensioni specificate
        convert "$image_file" -resize "${SIZE}>" "$temp_file"
        
        # Sostituisci il file originale con quello ridimensionato
        mv "$temp_file" "$image_file"
        
        # Mostra le nuove dimensioni
        identify -format "  Dimensioni: %wx%h" "$image_file"
        echo ""
        
        count_elaborati=$((count_elaborati + 1))
    else
        count_saltati=$((count_saltati + 1))
        # Commenta la riga successiva per non vedere i file saltati
        # echo "Salto: $image_file (non corrisponde al pattern <codice>.jpg)"
    fi
done

echo "=== Riepilogo ==="
echo "Directory elaborata: $BASE_DIR"
echo "Dimensioni impostate: ${SIZE}px"
echo "Immagini elaborate: $count_elaborati"
echo "Immagini saltate: $count_saltati"
echo "Conversione completata!"
