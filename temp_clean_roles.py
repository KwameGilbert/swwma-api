import os
import re

base_path = r'c:\Users\G.E.Kukah\code\swwma-api\src'

patterns = [
    (r'User::ROLE_SUPER_ADMIN,\s*', ''),
    (r',\s*User::ROLE_SUPER_ADMIN', ''),
    (r'User::ROLE_SUPER_ADMIN', 'User::ROLE_ADMIN'), # Fallback
]

for root, dirs, files in os.walk(base_path):
    for file in files:
        if file.endswith('.php'):
            path = os.path.join(root, file)
            with open(path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            new_content = content
            for pattern, subst in patterns:
                new_content = re.sub(pattern, subst, new_content)
            
            if new_content != content:
                print(f"Updating {path}")
                with open(path, 'w', encoding='utf-8') as f:
                    f.write(new_content)

print("Done.")
