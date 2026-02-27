# 🚀 100 Million Row Challenge - Full PostgreSQL & Docker Edition

This solution achieves **maximum delegation to PostgreSQL 17**, using PHP only as a minimal 10-line wrapper to write the final string to disk.

## 📋 Prerequisites

- **Docker**
- **Docker Compose**

## 🛠️ Setup

1.  **Build the application image:**
    ```bash
    docker compose build app
    ```

2.  **Start the PostgreSQL database:**
    The database is RAM-backed (`tmpfs`) and tuned for ephemeral performance (`fsync=off`).
    ```bash
    docker compose up -d db
    ```

## ✅ Validation

The solution uses a single SQL query to generate the exact, pretty-printed, ordered JSON required by the challenge:
```bash
docker compose run app data:validate
```

## 🏎️ Benchmark

1.  **Generate the dataset (e.g., 100 million rows):**
    ```bash
    docker compose run app data:generate 100000000
    ```

2.  **Run the parser:**
    ```bash
    docker compose run app data:parse
    ```

## 🏗️ Architecture

- **PostgreSQL 17 (Alpine):** Handles all heavy lifting. Data is imported via the `COPY` command.
- **Pure SQL JSON Construction:** Uses CTEs and `jsonb_pretty` to construct the entire output object. 
- **Formatting:** A custom `regexp_replace` pattern ensures the output exactly matches PHP's `JSON_PRETTY_PRINT` format, including the required `\/` escaping for the validator.
- **Order Preservation:** Uses a `SERIAL` ID during import to maintain appearance order as required.
- **Minimal PHP:** Only 10 lines of functional code in `Parser.php` to connect, query, and write the final file.

## 🧹 Cleanup

To stop and remove the containers:
```bash
docker compose down
```
