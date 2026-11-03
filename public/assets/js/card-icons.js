(function () {
  const iconRules = [
    { pattern: /immuniz|vaccin|epi program/, icon: 'fa-syringe' },
    { pattern: /maternal|pregnan|prenatal|postnatal|antenatal/, icon: 'fa-person-pregnant' },
    { pattern: /ncd|diabetes|hypertension|blood pressure|chronic care|philpen/, icon: 'fa-heart-pulse' },
    { pattern: /family planning|contracept|reproductive/, icon: 'fa-heart' },
    { pattern: /medicine|medication|inventory|stock|batch|suppl|dispens/, icon: 'fa-pills' },
    { pattern: /reminder|notification|sms/, icon: 'fa-bell' },
    { pattern: /forecast|analytics|report|trend|chart/, icon: 'fa-chart-line' },
    { pattern: /appointment|visit|schedule|encounter/, icon: 'fa-calendar-check' },
    { pattern: /patient|client|user|account|registry|resident/, icon: 'fa-users' },
    { pattern: /activity|audit|log/, icon: 'fa-clipboard-list' },
    { pattern: /\btcl\b|register|list/, icon: 'fa-list-check' },
    { pattern: /login|password|authentication/, icon: 'fa-lock' }
  ];

  function isCard(element) {
    const classes = Array.from(element.classList);
    const hasCardClass = classes.some((name) =>
      ['card', 'login-card', 'data-card', 'kpi-card'].includes(name)
    );
    if (hasCardClass) return true;

    return classes.some((name) => name.startsWith('bg-'))
      && classes.some((name) => name.startsWith('rounded'))
      && classes.some((name) => /^p-\d/.test(name))
      && classes.some((name) => name.startsWith('border') || name.startsWith('shadow'));
  }

  function hasExistingIcon(card) {
    return Array.from(card.querySelectorAll('i[class*="fa-"]')).some((icon) => {
      let ancestor = icon.parentElement;
      while (ancestor && ancestor !== card) {
        if (isCard(ancestor) || ancestor.matches('a, button')) return false;
        ancestor = ancestor.parentElement;
      }
      return ancestor === card;
    });
  }

  function iconFor(card) {
    const content = card.textContent.toLowerCase();
    const match = iconRules.find((rule) => rule.pattern.test(content));
    return match ? match.icon : 'fa-notes-medical';
  }

  document.querySelectorAll('div, a, section, article, li').forEach((card) => {
    if (!isCard(card) || hasExistingIcon(card)) return;

    const badge = document.createElement('span');
    badge.className = 'healthlogs-card-icon';
    badge.setAttribute('aria-hidden', 'true');

    const icon = document.createElement('i');
    icon.className = 'fas ' + iconFor(card);
    badge.appendChild(icon);

    card.classList.add('healthlogs-card');
    card.appendChild(badge);
  });
}());
