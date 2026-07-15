# 🛠️ Como converter `fswwp-update-checker` de submódulo para diretório normal

Comandos para copiar do **terminal** (Git Bash ou PowerShell no Windows):

## 1. Remover o gitlink do índice

```bash
git rm --cached fswwp-update-checker
```

## 2. Apagar o `.git` interno do submódulo

```bash
rmdir /s /q fswwp-update-checker\.git
```

> ⚠️ Isso remove o vínculo com o repositório remoto `https://github.com/euzebiodeveloper/fswwp-update-checker.git`.
> Os arquivos **permanecem no disco** — só perde o histórico de commits daquele repositório separado.

## 3. Adicionar os arquivos ao git (já como arquivos normais)

```bash
git add fswwp-update-checker\
```

## 4. Commitar

```bash
git commit -m "fix: converte fswwp-update-checker de submodulo para diretorio normal"
```

## 5. Enviar para o GitHub

```bash
git push
```

---

### ✅ Resultado esperado

- A pasta `fswwp-update-checker/` vai mostrar os 3 arquivos normalmente no GitHub:
  - `class-fswwp-update-checker.php`
  - `index.php`
  - `readme.txt`
- O aviso `warning: adding embedded git repository` **não vai mais aparecer**
- O `git status` vai mostrar os arquivos como qualquer outro arquivo do repositório

### 🧹 Alternativa: manter como submódulo (caso prefira)

```bash
git submodule add https://github.com/euzebiodeveloper/fswwp-update-checker.git fswwp-update-checker
```

Isso recria o `.gitmodules` e o submódulo volta a funcionar normalmente no GitHub.
