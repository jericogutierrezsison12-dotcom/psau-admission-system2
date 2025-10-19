{ pkgs }: {
  deps = [
    pkgs.python311
    pkgs.python311Packages.pip
    pkgs.python311Packages.setuptools
    pkgs.python311Packages.wheel
    pkgs.python311Packages.flask
    pkgs.python311Packages.flask-cors
    pkgs.python311Packages.opencv4
    pkgs.python311Packages.pillow
    pkgs.python311Packages.numpy
    pkgs.python311Packages.scikit-learn
    pkgs.python311Packages.joblib
    pkgs.python311Packages.werkzeug
    pkgs.python311Packages.pdf2image
    pkgs.python311Packages.pymupdf
    pkgs.python311Packages.mysql-connector-python
    pkgs.python311Packages.paddleocr
  ];
}
