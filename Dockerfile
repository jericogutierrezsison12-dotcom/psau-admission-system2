FROM python:3.9-slim

# Set working directory
WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libgl1-mesa-glx \
    libglib2.0-0 \
    libsm6 \
    libxext6 \
    libxrender-dev \
    libgomp1 \
    libgcc-s1 \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements first for better caching
COPY python/image/requirements.txt .

# Install Python dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Copy application code
COPY python/image/ .

# Create uploads directory
RUN mkdir -p uploads models

# Expose port
EXPOSE 10000

# Set environment variables
ENV FLASK_ENV=production
ENV FLASK_DEBUG=false
ENV PORT=10000

# Run the application
CMD ["gunicorn", "app:app", "--bind", "0.0.0.0:$PORT", "--workers", "2", "--timeout", "120"]
