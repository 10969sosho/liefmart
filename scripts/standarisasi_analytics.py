#!/usr/bin/env python3
"""Standarisasi halaman analytics: tambah design-system.css + hapus CSS redundant."""

import re
import os

BASE_DIR = "/Users/10969sosho/PROJECT/CVSS/SUDAH_TAYANG/PAK RUDI/Liefmart"
APPS = ["livemart", "hgn"]

STANDALONE_FILES = [
    "internal_product_sales.blade.php",
    "monthly_sales_summary.blade.php",
    "offline_monthly_sales_summary.blade.php",
    "offline_sales_by_customer.blade.php",
    "offline_sales_by_product.blade.php",
    "offline_sales_detail_report.blade.php",
    "produk_internal_terlaris.blade.php",
    "produk_platform_terlaris.blade.php",
    "sales_by_date_number.blade.php",
    "sales_by_day_of_week.blade.php",
    "sales_by_master_product_new.blade.php",
    "sales_by_master_product_special.blade.php",
    "sales_by_platform.blade.php",
    "sales_by_platform_product.blade.php",
    "sales_by_status_day.blade.php",
    "sales_detail_report.blade.php",
    "sales_export_mapped.blade.php",
]

DESIGN_SYSTEM_LINK = '<link rel="stylesheet" href="{{ asset(\'css/design-system.css\') }}">'


def is_card_override_block(block_lines):
    """Check if a multi-line .card block is a Bootstrap override (border-radius, border: none, box-shadow etc)."""
    content = ' '.join(block_lines)
    return ('border-radius' in content and 'box-shadow' in content) or \
           ('border-radius' in content and 'border' in content and 'margin-bottom' in content)


def is_card_hover_override_block(block_lines):
    content = ' '.join(block_lines)
    return 'transform' in content or 'translateY' in content


def is_btn_override_block(block_lines):
    content = ' '.join(block_lines)
    return 'border-radius' in content and 'font-weight' in content and \
           ('padding' in content or 'transition' in content)


def is_btn_primary_override_block(block_lines):
    content = ' '.join(block_lines)
    return '--primary-color' in content and 'background-color' in content


def is_btn_primary_hover_override_block(block_lines):
    content = ' '.join(block_lines)
    return '--secondary-color' in content and 'background-color' in content


def is_btn_outline_primary_override(block_lines):
    content = ' '.join(block_lines)
    return '--primary-color' in content and 'color' in content and 'border-color' in content


def is_btn_outline_primary_hover_override(block_lines):
    content = ' '.join(block_lines)
    return '--primary-color' in content and 'background-color' in content and 'color: white' in content


def is_table_override_block(block_lines):
    """Only match simple .table blocks with just border-radius/overflow, not ones with nth-child etc."""
    content = ' '.join(block_lines)
    return 'border-radius' in content and 'overflow' in content and \
           'nth-child' not in content and 'min-width' not in content


def is_form_control_block(block_lines):
    content = ' '.join(block_lines)
    return 'border-radius' in content and 'border' in content


def is_form_control_focus_block(block_lines):
    content = ' '.join(block_lines)
    return 'box-shadow' in content and 'border-color' in content


MULTILINE_DETECTORS = {
    'card': (re.compile(r'^\s*\.card\s*\{\s*$'), is_card_override_block),
    'card:hover': (re.compile(r'^\s*\.card:hover\s*\{\s*$'), is_card_hover_override_block),
    'btn': (re.compile(r'^\s*\.btn\s*\{\s*$'), is_btn_override_block),
    'btn-primary': (re.compile(r'^\s*\.btn-primary\s*\{\s*$'), is_btn_primary_override_block),
    'btn-primary:hover': (re.compile(r'^\s*\.btn-primary:hover\s*\{\s*$'), is_btn_primary_hover_override_block),
    'btn-outline-primary': (re.compile(r'^\s*\.btn-outline-primary\s*\{\s*$'), is_btn_outline_primary_override),
    'btn-outline-primary:hover': (re.compile(r'^\s*\.btn-outline-primary:hover\s*\{\s*$'), is_btn_outline_primary_hover_override),
    'table': (re.compile(r'^\s*\.table\s*\{\s*$'), is_table_override_block),
    'form-control-select': (re.compile(r'^\s*\.form-control,\s*\.form-select\s*\{\s*$'), is_form_control_block),
    'form-control-select-focus': (re.compile(r'^\s*\.form-control:focus,\s*\.form-select:focus\s*\{\s*$'), is_form_control_focus_block),
}

SINGLE_LINE_PATTERNS = [
    # .card overrides
    (re.compile(r'^\s*\.card\s*\{\s*border-radius:\s*[^}]*box-shadow:\s*[^}]*\}\s*$'), '.card'),
    (re.compile(r'^\s*\.card\s*\{\s*border-radius:\s*[^}]*border:\s*[^}]*margin-bottom:\s*[^}]*background:\s*[^}]*\}\s*$'), '.card'),
    (re.compile(r'^\s*\.card:hover\s*\{\s*transform:\s*[^}]*\}\s*$'), '.card:hover'),
    # .btn overrides
    (re.compile(r'^\s*\.btn\s*\{\s*border-radius:\s*[^}]*padding:\s*[^}]*font-weight:\s*[^}]*\}\s*$'), '.btn'),
    (re.compile(r'^\s*\.btn\s*\{\s*border-radius:\s*[^}]*padding:\s*[^}]*font-weight:\s*[^}]*transition:\s*[^}]*\}\s*$'), '.btn'),
    # .btn-primary overrides
    (re.compile(r'^\s*\.btn-primary\s*\{\s*background-color:\s*var\(--primary-color\)[^}]*\}\s*$'), '.btn-primary'),
    (re.compile(r'^\s*\.btn-primary:hover\s*\{\s*background-color:\s*var\(--secondary-color\)[^}]*\}\s*$'), '.btn-primary:hover'),
    # .btn-outline-primary overrides
    (re.compile(r'^\s*\.btn-outline-primary\s*\{\s*color:\s*var\(--primary-color\)[^}]*\}\s*$'), '.btn-outline-primary'),
    (re.compile(r'^\s*\.btn-outline-primary:hover\s*\{\s*background-color:\s*var\(--primary-color\)[^}]*\}\s*$'), '.btn-outline-primary:hover'),
    # .table override
    (re.compile(r'^\s*\.table\s*\{\s*border-radius:\s*[^}]*overflow:\s*[^}]*\}\s*$'), '.table'),
    # .form-control, .form-select
    (re.compile(r'^\s*\.form-control,\s*\.form-select\s*\{\s*border-radius:\s*[^}]*border:\s*[^}]*padding:\s*[^}]*\}\s*$'), '.form-control, .form-select'),
    # .form-control:focus, .form-select:focus
    (re.compile(r'^\s*\.form-control:focus,\s*\.form-select:focus\s*\{\s*box-shadow:\s*[^}]*border-color:\s*[^}]*\}\s*$'), '.form-control:focus, .form-select:focus'),
    # Comment headers
    (re.compile(r'^\s*/\*\s*Custom styles for cards\s*\*/\s*$'), 'comment'),
    (re.compile(r'^\s*/\*\s*Custom button styles\s*\*/\s*$'), 'comment'),
    (re.compile(r'^\s*/\*\s*Table styles\s*\*/\s*$'), 'comment'),
    (re.compile(r'^\s*/\*\s*Form controls\s*\*/\s*$'), 'comment'),
]


def process_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    original = content
    changes_summary = set()

    # Step 1: Add design-system.css link
    if 'design-system.css' not in content:
        link_pattern = re.compile(r'(<link[^>]*stylesheet[^>]*>)', re.IGNORECASE)
        matches = list(link_pattern.finditer(content))
        if matches:
            last_link = matches[-1]
            insert_pos = last_link.end()
            content = content[:insert_pos] + '\n    ' + DESIGN_SYSTEM_LINK + content[insert_pos:]
            changes_summary.add('+ design-system.css link')
    else:
        changes_summary.add('(design-system.css already present)')

    # Step 2: Remove redundant CSS
    lines = content.split('\n')
    removed_selectors = set()
    new_lines = []
    i = 0

    while i < len(lines):
        line = lines[i]
        matched = False

        # Check single-line patterns first
        for pattern, label in SINGLE_LINE_PATTERNS:
            if pattern.match(line):
                removed_selectors.add(label)
                matched = True
                break

        if matched:
            i += 1
            continue

        # Check multi-line blocks
        multi_matched = False
        for name, (start_pattern, validator) in MULTILINE_DETECTORS.items():
            if start_pattern.match(line):
                j = i + 1
                block_lines = []
                while j < len(lines) and not re.match(r'^\s*\}\s*$', lines[j]):
                    block_lines.append(lines[j].strip())
                    j += 1
                if validator(block_lines):
                    removed_selectors.add(name)
                    i = j + 1  # skip past closing }
                    multi_matched = True
                    break
                else:
                    # Not an override block, keep it
                    new_lines.append(line)
                    i += 1
                    multi_matched = True
                    break

        if multi_matched:
            continue

        new_lines.append(line)
        i += 1

    content = '\n'.join(new_lines)

    # Clean excessive blank lines (max 2 consecutive)
    content = re.sub(r'\n{4,}', '\n\n\n', content)

    if original != content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        return True, sorted(changes_summary), sorted(removed_selectors)
    else:
        return False, sorted(changes_summary), sorted(removed_selectors)


def main():
    results = {}

    for app in APPS:
        for filename in STANDALONE_FILES:
            filepath = os.path.join(BASE_DIR, "apps", app, "resources", "views", "analytics", filename)
            if not os.path.exists(filepath):
                print(f"[MISSING] {app}/{filename}")
                continue

            modified, changes, removed = process_file(filepath)
            key = f"{app}/{filename}"
            results[key] = (modified, removed)
            status = "UPDATED" if modified else "unchanged"
            print(f"[{status}] {key}")
            for c in changes:
                print(f"       {c}")
            if removed:
                print(f"       removed CSS: {', '.join(removed)}")

    print("\n" + "=" * 60)
    print("RINGKASAN")
    print("=" * 60)
    updated = [(k, v[1]) for k, v in results.items() if v[0]]
    unchanged = [k for k, v in results.items() if not v[0]]

    print(f"\nTotal file diproses: {len(results)}")
    print(f"Diupdate: {len(updated)}")
    print(f"Tidak berubah: {len(unchanged)}")

    if updated:
        print("\n--- File yang DIUPDATE ---")
        for key, removed in updated:
            print(f"  {key}")
            if removed:
                print(f"    CSS dihapus: {', '.join(removed)}")

    if unchanged:
        print("\n--- File TIDAK BERUBAH ---")
        for key in unchanged:
            print(f"  {key}")

    return results


if __name__ == "__main__":
    main()
