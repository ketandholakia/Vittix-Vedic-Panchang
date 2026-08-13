import re
import os

def parse_php_file(php_path, threshold=1e-7):
    with open(php_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # We are looking for blocks like:
    # public static function calculateA0($t): float
    # {
    #     return  4.40250710144 * cos(0.00000000000 + 0.00000000000 * $t)
    #         + 0.40989414976 * cos(1.48302034194 + 26087.90314157420 * $t) ...

    # Let's find all methods
    method_pattern = re.compile(r'public static function calculate([A-C])([0-9])\(\$t\): float\s*\{\s*return(.*?)^\s*\}', re.MULTILINE | re.DOTALL)
    
    term_pattern = re.compile(r'([+-]?\s*[0-9\.]+)\s*\*\s*cos\(\s*([0-9\.]+)\s*\+\s*([0-9\.]+)\s*\*\s*\$t\s*\)')

    results = {}
    for match in method_pattern.finditer(content):
        series = match.group(1) # A=L, B=B, C=R
        power = match.group(2) # 0,1,2,3,4,5
        body_expr = match.group(3)

        if series == 'A':
            s = 'L'
        elif series == 'B':
            s = 'B'
        else:
            s = 'R'

        var_name = f"{s}{power}"
        
        terms = []
        for tmatch in term_pattern.finditer(body_expr):
            amp = float(tmatch.group(1).replace(' ', ''))
            phase = float(tmatch.group(2))
            freq = float(tmatch.group(3))
            
            if abs(amp) >= threshold:
                terms.append((amp, phase, freq))
        
        if terms:
            results[var_name] = terms

    return results

def generate_php_class(class_name, parsed_data, out_path):
    lines = []
    lines.append("<?php\n")
    lines.append("declare(strict_types=1);\n")
    lines.append("namespace Vittix\\Panchang\\Astronomy\\Planetary;\n")
    lines.append(f"/**\n * VSOP87 truncated terms for {class_name.replace('Terms', '')}.\n */")
    lines.append(f"final class {class_name}\n{{")
    
    # ensure sorted L0, L1...
    keys = sorted(parsed_data.keys(), key=lambda x: (x[0], int(x[1:])))
    for k in keys:
        terms = parsed_data[k]
        lines.append(f"    public const {k} = [")
        for term in terms:
            lines.append(f"        [{term[0]:.11f}, {term[1]:.11f}, {term[2]:.11f}],")
        lines.append("    ];\n")
    
    lines.append("}")
    
    os.makedirs(os.path.dirname(out_path), exist_ok=True)
    with open(out_path, 'w', encoding='utf-8') as f:
        f.write("\n".join(lines) + "\n")

if __name__ == "__main__":
    base_dir = r"d:\ketan\github\vittixvedicpanchang\scratch\astronomy-bundle\src\Calculations\VSOP87"
    out_dir = r"d:\ketan\github\vittixvedicpanchang\src\Astronomy\Planetary"
    
    planets = ["Mercury", "Venus", "Mars", "Jupiter", "Saturn"]
    for p in planets:
        data = parse_php_file(os.path.join(base_dir, f"{p}SphericalVSOP87.php"))
        generate_php_class(f"{p}Terms", data, os.path.join(out_dir, f"{p}Terms.php"))

    earth = parse_php_file(os.path.join(base_dir, "EarthSphericalVSOP87.php"))
    generate_php_class("EarthTerms", earth, os.path.join(out_dir, "EarthTerms.php"))
    
    print("Done")
