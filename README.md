# Excel / CSV File Splitter

A lightweight PHP web application for uploading large Excel or CSV files, counting rows, splitting them into smaller chunks, and downloading the output as a ZIP archive.

## Features

- Upload CSV and Excel files through a simple web interface
- Detect and count data rows in a streaming way
- Split files into smaller chunk files using a configurable chunk size
- Preserve the header row in each generated file
- Run splitting as a background job and monitor progress
- Download the final ZIP archive with all split parts
- Store job metadata in PostgreSQL

## Technology Stack

- PHP 8.2+
- PostgreSQL
- Composer
- OpenSpout for spreadsheet reading/writing
- Core PHP MVC structure without a framework

## Project Structure

- public/ - Web entry point and static assets
- src/ - Application logic, controllers, services, and models
- scripts/ - Installer and background worker scripts
- storage/ - Uploaded files, temporary outputs, and archives
- tests/ - Basic test coverage for splitting logic

## Requirements

Make sure the following are installed and enabled:

- PHP 8.2 or higher
- Composer
- PostgreSQL server
- PHP extensions: pdo, pdo_pgsql, zip, mbstring, openssl

## Installation

1. Clone or open the project folder.
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Create a .env file in the project root with your database configuration:
   ```env
   DB_HOST=localhost
   DB_PORT=5432
   DB_DATABASE=file_splitter
   DB_USERNAME=postgres
   DB_PASSWORD=postgres
   STORAGE_DIR=storage
   APP_ENV=local
   ```
4. Run the installer to create the database and tables:
   ```bash
   php scripts/install.php
   ```

## Running the Application

Start the built-in PHP server from the project root:

```bash
php -S localhost:8000 -t public
```

Then open:

```text
http://localhost:8000/
```

## Usage

1. Upload a CSV or Excel file.
2. The app will count rows and suggest chunk sizes.
3. Choose a chunk size and start the split job.
4. Wait for the background worker to finish.
5. Download the generated ZIP file containing the split parts.

## Notes

- Uploaded files are stored outside the public web folder for security.
- The application uses a background worker process for long-running split jobs.
- If the database is not ready, the installer will attempt to create the target database automatically.

## License

This project is intended for local development and internal use.
