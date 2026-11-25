"""Exporta o cabeçalho e rodapé do site para includes/layout-*.html (usados pelas páginas PHP do blog).

Uso:  python tools/export_layout.py
Execute de novo sempre que o menu, o rodapé ou o <head> mudarem em tools/template.py.
"""
import os, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from template import page  # noqa: E402

html = page('blog.php', '@@TITLE@@', '@@DESC@@', '@@BODY@@', 'blog.php')
top, bottom = html.split('@@BODY@@')
os.makedirs('includes', exist_ok=True)
open('includes/layout-top.html', 'w', encoding='utf-8', newline='\n').write(top)
open('includes/layout-bottom.html', 'w', encoding='utf-8', newline='\n').write(bottom)
print('includes/layout-top.html e layout-bottom.html actualizados')
