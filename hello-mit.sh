#!/bin/bash

# Styling colors
GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color
BOLD='\033[1m'

echo -e "${CYAN}${BOLD}====================================================${NC}"
echo -e "${CYAN}${BOLD}          GIT BACKDATED COMMIT UTILITY              ${NC}"
echo -e "${CYAN}${BOLD}====================================================${NC}"

# Define dates for February 2026
DATES=(
  "2026-02-01T12:00:00"
  "2026-02-02T12:00:00"
  "2026-02-06T12:00:00"
  "2026-02-07T12:00:00"
)

DATE_LABELS=(
  "Feb 1st, 2026 (Sunday)"
  "Feb 2nd, 2026 (Monday)"
  "Feb 6th, 2026 (Friday)"
  "Feb 7th, 2026 (Saturday)"
)

# Check git status
HAS_STAGED=false
if ! git diff --cached --quiet; then
  HAS_STAGED=true
fi

if [ "$HAS_STAGED" = true ]; then
  echo -e "${GREEN}✓ Detected staged changes ready to be committed!${NC}"
else
  echo -e "${YELLOW}⚠ No staged changes detected.${NC}"
  echo -e "Commits will be created using the ${BOLD}--allow-empty${NC} flag."
  echo -e "This is perfect for creating contribution history without modifying files."
fi

echo -e "\n${BOLD}Select a date option:${NC}"
for i in "${!DATE_LABELS[@]}"; do
  echo -e "  ${CYAN}$((i+1)))${NC} ${DATE_LABELS[$i]} (${DATES[$i]})"
done
echo -e "  ${CYAN}5)${NC} All of the above (creates 4 commits, one for each date)"
echo -e "  ${CYAN}6)${NC} Exit"

echo -ne "\nEnter option (1-6): "
read -r OPTION

if [ "$OPTION" -lt 1 ] || [ "$OPTION" -gt 6 ]; then
  echo -e "${RED}Invalid option. Exiting.${NC}"
  exit 1
fi

if [ "$OPTION" -eq 6 ]; then
  echo -e "Exiting. No commits made."
  exit 0
fi

echo -ne "Enter commit message [Default: 'backdate-commit']: "
read -r MSG
if [ -z "$MSG" ]; then
  MSG="backdate-commit"
fi

make_commit() {
  local commit_date=$1
  local commit_msg=$2
  
  echo -e "\n${YELLOW}Creating commit for date: ${BOLD}$commit_date${NC}..."
  
  if [ "$HAS_STAGED" = true ]; then
    # Commit staged changes
    GIT_AUTHOR_DATE="$commit_date" GIT_COMMITTER_DATE="$commit_date" git commit -m "$commit_msg"
  else
    # Create empty commit
    GIT_AUTHOR_DATE="$commit_date" GIT_COMMITTER_DATE="$commit_date" git commit --allow-empty -m "$commit_msg"
  fi
  
  if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Successfully created commit!${NC}"
  else
    echo -e "${RED}✗ Failed to create commit.${NC}"
  fi
}

if [ "$OPTION" -eq 5 ]; then
  echo -e "\n${YELLOW}Preparing to create 4 backdated commits...${NC}"
  for i in "${!DATES[@]}"; do
    # Re-evaluate staged changes dynamically after each commit
    if ! git diff --cached --quiet; then
      HAS_STAGED=true
    else
      HAS_STAGED=false
    fi
    make_commit "${DATES[$i]}" "$MSG"
  done
else
  INDEX=$((OPTION-1))
  make_commit "${DATES[$INDEX]}" "$MSG"
fi

echo -e "\n${GREEN}${BOLD}Done!${NC} Remember to push your changes using: ${CYAN}git push${NC}"
