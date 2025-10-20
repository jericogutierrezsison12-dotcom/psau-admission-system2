# PSAU OCR Service Integration

This Flask application provides OCR (Optical Character Recognition) services for the PSAU Admission System. It analyzes uploaded documents to detect report cards and identify failed remarks.

## Features

- **Document Analysis**: Uses PaddleOCR to extract text from PDF and image files
- **Report Card Detection**: ML-powered classification to identify report cards
- **Failed Remarks Detection**: Analyzes extracted text for failure indicators
- **Real-time Processing**: Provides instant feedback during file upload
- **PHP Integration**: Seamlessly integrates with the existing PHP application

## Setup Instructions

### 1. Install Python Dependencies

```bash
pip install -r requirements.txt
```

### 2. Start the Flask Service

**Option A: Using the batch file (Windows)**
```bash
start_flask_app.bat
```

**Option B: Direct Python execution**
```bash
python app.py
```

The service will start on `http://localhost:5000`

### 3. Test the Integration

```bash
python test_integration.py
```

## API Endpoints

### `GET /`
Returns service status and available endpoints.

### `POST /ocr_service`
Main OCR processing endpoint for PHP integration.

**Request:**
- `file`: PDF or image file (multipart/form-data)

**Response:**
```json
{
  "success": true,
  "prediction": "Report Card",
  "status_info": {
    "status": "passed",
    "message": "You have passed"
  },
  "texts": [...],
  "total_texts": 25,
  "is_report_card": true,
  "has_failed_remarks": false
}
```

## Integration with PHP Application

The Flask service is integrated into the application form (`application_form.php`) in the following ways:

1. **Real-time Analysis**: JavaScript calls the OCR service when a file is uploaded
2. **Server-side Processing**: PHP calls the OCR service during form submission
3. **Database Storage**: OCR results are stored in the applications table

### Database Schema Updates

The following columns should be added to the `applications` table:

```sql
ALTER TABLE applications ADD COLUMN ocr_analysis TEXT;
ALTER TABLE applications ADD COLUMN ocr_prediction VARCHAR(50);
ALTER TABLE applications ADD COLUMN ocr_status_info TEXT;
```

## Configuration

### Flask App Configuration
- **Host**: 0.0.0.0 (allows external connections)
- **Port**: 5000
- **Max File Size**: 16MB
- **Upload Folder**: `uploads/`

### PHP Integration
- **Flask URL**: `http://localhost:5000/ocr_service`
- **Timeout**: 60 seconds
- **File Types**: PDF, JPG, JPEG, PNG, GIF, BMP, TIFF

## Troubleshooting

### Common Issues

1. **Connection Refused**
   - Ensure Flask app is running on port 5000
   - Check firewall settings
   - Verify the URL in PHP code

2. **OCR Model Loading Errors**
   - Check internet connection (first run downloads models)
   - Ensure sufficient disk space
   - Try running with offline mode

3. **File Upload Errors**
   - Check file size limits
   - Verify file format support
   - Ensure uploads directory exists

### Logs

Check the console output for detailed error messages and processing logs.

## Development

### Adding New Features

1. Modify `app.py` for new endpoints
2. Update `application_form.php` for PHP integration
3. Update `application_form.js` for frontend changes
4. Test with `test_integration.py`

### Model Training

The OCR service uses pre-trained models. To retrain or update models:

1. Place training data in appropriate directories
2. Run the training script (if available)
3. Update model paths in `app.py`

## Security Notes

- The service runs on localhost by default
- File uploads are temporarily stored and cleaned up
- No persistent storage of uploaded files
- CORS is enabled for PHP integration

## Performance

- First request may be slower due to model loading
- Subsequent requests are faster
- Consider running as a service for production use
- Monitor memory usage with large files