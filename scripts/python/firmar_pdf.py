#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Firma Digital de PDFs para Comunidad Campesina Callqui Chico
Usando PyHanko para firma digital con certificado .p12/.pfx
"""

import sys
import os
import json
import argparse
from datetime import datetime
from io import BytesIO

try:
    from pyhanko.pdf_utils.incremental_writer import IncrementalPdfFileWriter
    from pyhanko.sign.signers import SimpleSigner
    from pyhanko.sign import sign_pdf, PdfSignatureMetadata
except ImportError as e:
    print(json.dumps({
        "success": False, 
        "message": f"PyHanko no instalado correctamente. Error: {str(e)}", 
        "output_pdf": ""
    }, ensure_ascii=False))
    sys.exit(1)


def firmar_pdf(input_pdf, output_pdf, cert_path, cert_password, firmante_nombre, firmante_rol, fecha_firma=None, posicion='footer'):
    """
    Firma digitalmente un PDF usando certificado .p12/.pfx
    """
    
    if not os.path.exists(input_pdf):
        return {"success": False, "message": f"PDF no encontrado: {input_pdf}", "output_pdf": ""}
    
    if not os.path.exists(cert_path):
        return {"success": False, "message": f"Certificado no encontrado: {cert_path}", "output_pdf": ""}
    
    if fecha_firma is None:
        fecha_firma = datetime.now().strftime("%d/%m/%Y %H:%M:%S")
    
    try:
        # Cargar certificado PKCS12
        signer = SimpleSigner.load_pkcs12(
            pfx_file=cert_path,
            passphrase=cert_password.encode('utf-8')
        )
        
        # Abrir PDF para escritura incremental - mantener archivo abierto
        f = open(input_pdf, 'rb')
        writer = IncrementalPdfFileWriter(f)
        
        # Generar nombre de campo único
        timestamp = datetime.now().strftime('%Y%m%d%H%M%S')
        field_name = f"Firma_{firmante_rol}_{timestamp}"
        
        # Crear metadata de firma
        sig_meta = PdfSignatureMetadata(
            field_name=field_name,
            reason=f'Firma digital - {firmante_rol}',
            location='Comunidad Campesina Callqui Chico',
            name=firmante_nombre
        )
        
        # Firmar usando sign_pdf
        sign_pdf(
            pdf_out=writer,
            signature_meta=sig_meta,
            signer=signer,
            in_place=False
        )
        
        # Guardar PDF firmado
        with open(output_pdf, 'wb') as out_f:
            writer.write(out_f)
        
        f.close()
        
        return {
            "success": True, 
            "message": "PDF firmado digitalmente", 
            "output_pdf": output_pdf,
            "firmante": firmante_nombre,
            "rol": firmante_rol,
            "fecha": fecha_firma,
            "field_name": field_name
        }
        
    except Exception as e:
        import traceback
        return {"success": False, "message": f"Error al firmar: {str(e)}", "output_pdf": "", "trace": traceback.format_exc()}


def main():
    """Función principal para CLI"""
    parser = argparse.ArgumentParser(description='Firmar PDF digitalmente - Comunidad Campesina Callqui Chico')
    parser.add_argument('input_pdf', help='Ruta del PDF a firmar')
    parser.add_argument('output_pdf', help='Ruta del PDF firmado')
    parser.add_argument('certificado', help='Ruta del certificado .p12/.pfx')
    parser.add_argument('password', help='Password del certificado')
    parser.add_argument('--firmante', required=True, help='Nombre del firmante')
    parser.add_argument('--rol', required=True, help='Rol del firmante (secretario/fiscal/tesorero/presidente)')
    parser.add_argument('--fecha', help='Fecha de firma (opcional)')
    parser.add_argument('--posicion', default='footer', help='Posición de firma (footer/header/last_page)')
    
    args = parser.parse_args()
    
    resultado = firmar_pdf(
        input_pdf=args.input_pdf,
        output_pdf=args.output_pdf,
        cert_path=args.certificado,
        cert_password=args.password,
        firmante_nombre=args.firmante,
        firmante_rol=args.rol,
        fecha_firma=args.fecha,
        posicion=args.posicion
    )
    
    print(json.dumps(resultado, ensure_ascii=False, indent=2))
    sys.exit(0 if resultado['success'] else 1)


if __name__ == '__main__':
    main()