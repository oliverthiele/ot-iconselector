import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import Modal from '@typo3/backend/modal.js';
import { SeverityEnum } from '@typo3/backend/enum/severity.js';

const DEBOUNCE_DELAY_MS = 250;
const MIN_SEARCH_LENGTH = 2;

class IconSelectorElement extends HTMLElement {
  connectedCallback() {
    this.selectedContainer = this.querySelector('.ot-iconselector-selected');
    this.inputGroup = this.querySelector('.ot-iconselector-input-group');
    this.searchInput = this.querySelector('.ot-iconselector-search');
    this.grid = this.querySelector('.ot-iconselector-grid');
    this.hiddenInput = this.querySelector('.ot-iconselector-value');
    this.favoritesButton = this.querySelector('.ot-iconselector-favorites-btn');

    if (!this.searchInput || !this.grid || !this.hiddenInput) {
      return;
    }

    this.ajaxUrl = this.dataset.ajaxUrl;
    this.favoriteUrl = this.dataset.favoriteUrl;
    this.iconDirectory = this.dataset.iconDirectory;
    this.iconStyle = this.dataset.iconStyle;
    this.maxResults = parseInt(this.dataset.maxResults ?? '36', 10);
    this.favoriteGroup = this.dataset.favoriteGroup ?? 'default';
    this.integratorFavorites = this.parseList(this.dataset.integratorFavorites);
    this.userFavorites = this.parseList(this.dataset.userFavorites);

    this.debounceTimer = null;
    this.activeIndex = -1;
    this.currentResults = [];

    this.applyGridStyles();
    this.bindRemoveButton();
    this.registerEventListeners();
  }

  parseList(value) {
    if (!value || value.trim() === '') {
      return [];
    }
    return value.split(',').map((item) => item.trim()).filter((item) => item !== '');
  }

  applyGridStyles() {
    this.grid.style.cssText = 'display:none;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:4px;max-height:320px;overflow-y:auto;padding:4px;border:1px solid var(--bs-border-color);border-radius:var(--bs-border-radius);margin-top:4px';
  }

  registerEventListeners() {
    this.searchInput.addEventListener('input', () => {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(
        () => this.search(this.searchInput.value.trim()),
        DEBOUNCE_DELAY_MS
      );
    });

    this.searchInput.addEventListener('keydown', (event) => this.handleKeydown(event));

    this.searchInput.addEventListener('focus', () => {
      const term = this.searchInput.value.trim();
      if (term.length >= MIN_SEARCH_LENGTH && this.currentResults.length > 0) {
        this.grid.style.display = 'grid';
      }
    });

    if (this.favoritesButton) {
      this.favoritesButton.addEventListener('click', () => this.openFavoritesModal());
    }
  }

  handleKeydown(event) {
    const columns = Math.floor(this.grid.offsetWidth / 80) || 1;

    switch (event.key) {
      case 'ArrowRight':
        event.preventDefault();
        this.setActiveIndex(this.activeIndex + 1);
        break;
      case 'ArrowLeft':
        event.preventDefault();
        this.setActiveIndex(this.activeIndex - 1);
        break;
      case 'ArrowDown':
        event.preventDefault();
        if (this.grid.style.display === 'none') {
          this.search(this.searchInput.value.trim());
        } else {
          this.setActiveIndex(this.activeIndex + columns);
        }
        break;
      case 'ArrowUp':
        event.preventDefault();
        this.setActiveIndex(this.activeIndex - columns);
        break;
      case 'Enter':
        event.preventDefault();
        if (this.activeIndex >= 0 && this.currentResults[this.activeIndex]) {
          this.selectItem(this.currentResults[this.activeIndex]);
        }
        break;
      case 'Escape':
        this.hideGrid();
        break;
    }
  }

  search(term) {
    if (term.length < MIN_SEARCH_LENGTH) {
      this.hideGrid();
      return;
    }

    new AjaxRequest(this.ajaxUrl)
      .withQueryArguments({
        search: term,
        iconDirectory: this.iconDirectory,
        iconStyle: this.iconStyle,
        maxResults: this.maxResults,
      })
      .get()
      .then(async (response) => {
        const data = await response.resolve();
        if (Array.isArray(data)) {
          this.showGrid(data);
        }
      })
      .catch(() => {
        this.hideGrid();
      });
  }

  showGrid(items) {
    this.currentResults = items;
    this.activeIndex = -1;
    this.grid.innerHTML = '';

    const allFavorites = [...new Set([...this.integratorFavorites, ...this.userFavorites])];

    items.forEach((item) => {
      const button = this.createIconButton(item, allFavorites);

      button.addEventListener('click', () => {
        this.selectItem(item);
      });

      this.grid.appendChild(button);
    });

    this.grid.style.display = items.length > 0 ? 'grid' : 'none';
  }

  createIconButton(item, allFavorites) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'ot-iconselector-item';
    button.dataset.identifier = item.identifier;
    button.title = item.identifier;
    button.style.cssText = 'position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:8px 4px;border:1px solid transparent;border-radius:var(--bs-border-radius);background:none;cursor:pointer;min-height:72px;transition:background-color .15s,border-color .15s';

    button.addEventListener('mouseenter', () => {
      button.style.backgroundColor = 'var(--bs-tertiary-bg)';
      button.style.borderColor = 'var(--bs-border-color)';
    });
    button.addEventListener('mouseleave', () => {
      if (!button.classList.contains('is-active')) {
        button.style.backgroundColor = '';
        button.style.borderColor = 'transparent';
      }
    });

    const svgWrap = document.createElement('div');
    svgWrap.style.cssText = 'width:32px;height:32px;flex-shrink:0';
    svgWrap.innerHTML = item.svg;
    button.appendChild(svgWrap);

    const label = document.createElement('span');
    label.style.cssText = 'font-size:10px;line-height:1.2;margin-top:4px;word-break:break-all;text-align:center;max-width:100%;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical';
    label.textContent = item.identifier;
    button.appendChild(label);

    if (allFavorites) {
      const isFavorite = allFavorites.includes(item.identifier);
      const isIntegrator = this.integratorFavorites.includes(item.identifier);
      const star = document.createElement('span');
      star.className = 'ot-iconselector-star';
      star.style.cssText = 'position:absolute;top:2px;right:2px;font-size:14px;cursor:pointer;opacity:' + (isFavorite ? '1' : '0.3');
      star.textContent = isFavorite ? '★' : '☆';
      star.title = isIntegrator ? 'Integrator-Favorit' : (isFavorite ? 'Favorit entfernen' : 'Als Favorit merken');
      if (!isIntegrator) {
        star.addEventListener('click', (event) => {
          event.stopPropagation();
          this.toggleFavorite(item.identifier, star);
        });
      }
      button.appendChild(star);
    }

    return button;
  }

  openFavoritesModal() {
    const allFavorites = [...new Set([...this.integratorFavorites, ...this.userFavorites])];
    if (allFavorites.length === 0) {
      return;
    }

    const searchTerms = allFavorites.join(' ');

    new AjaxRequest(this.ajaxUrl)
      .withQueryArguments({
        search: allFavorites[0],
        iconDirectory: this.iconDirectory,
        iconStyle: this.iconStyle,
        maxResults: 100,
      })
      .get()
      .then(async () => {
        this.loadFavoriteIcons(allFavorites);
      });
  }

  loadFavoriteIcons(identifiers) {
    const requests = identifiers.map((identifier) =>
      new AjaxRequest(this.ajaxUrl)
        .withQueryArguments({
          search: identifier,
          iconDirectory: this.iconDirectory,
          iconStyle: this.iconStyle,
          maxResults: 5,
        })
        .get()
        .then(async (response) => {
          const data = await response.resolve();
          if (Array.isArray(data)) {
            return data.find((item) => item.identifier === identifier) ?? null;
          }
          return null;
        })
        .catch(() => null)
    );

    Promise.all(requests).then((results) => {
      const icons = results.filter((item) => item !== null);
      this.showFavoritesModal(icons);
    });
  }

  showFavoritesModal(icons) {
    const gridContainer = document.createElement('div');
    gridContainer.style.cssText = 'display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:8px;padding:8px';

    icons.forEach((item) => {
      const isIntegrator = this.integratorFavorites.includes(item.identifier);
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'btn btn-outline-secondary';
      button.style.cssText = 'display:flex;flex-direction:column;align-items:center;justify-content:center;padding:12px 8px;min-height:90px;position:relative';
      button.title = item.identifier;

      const svgWrap = document.createElement('div');
      svgWrap.style.cssText = 'width:32px;height:32px;flex-shrink:0;margin-bottom:6px';
      svgWrap.innerHTML = item.svg;
      button.appendChild(svgWrap);

      const label = document.createElement('span');
      label.style.cssText = 'font-size:11px;line-height:1.2;word-break:break-all;text-align:center;max-width:100%;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical';
      label.textContent = item.identifier;
      button.appendChild(label);

      if (!isIntegrator) {
        const removeBtn = document.createElement('span');
        removeBtn.style.cssText = 'position:absolute;top:2px;right:4px;font-size:12px;cursor:pointer;opacity:0.5;line-height:1';
        removeBtn.textContent = '×';
        removeBtn.title = 'Favorit entfernen';
        removeBtn.addEventListener('click', (event) => {
          event.stopPropagation();
          this.toggleFavorite(item.identifier, null);
          button.remove();
        });
        button.appendChild(removeBtn);
      }

      button.addEventListener('click', () => {
        this.selectItem(item);
        Modal.dismiss();
      });

      gridContainer.appendChild(button);
    });

    const modal = Modal.advanced({
      title: 'Favoriten',
      content: gridContainer,
      severity: SeverityEnum.info,
      size: Modal.sizes.medium,
      buttons: [
        {
          text: 'Schließen',
          btnClass: 'btn-default',
          trigger: (event, modalInstance) => modalInstance.hideModal(),
        },
      ],
    });
  }

  hideGrid() {
    this.grid.style.display = 'none';
    this.activeIndex = -1;
  }

  selectItem(item) {
    this.hiddenInput.value = item.identifier;

    this.selectedContainer.innerHTML = '';
    const preview = document.createElement('div');
    preview.className = 'd-flex align-items-center gap-2 p-2 border rounded';

    const svgWrap = document.createElement('div');
    svgWrap.className = 'ot-iconselector-preview-svg';
    svgWrap.style.cssText = 'width:32px;height:32px;flex-shrink:0';
    svgWrap.innerHTML = item.svg;
    preview.appendChild(svgWrap);

    const identifierLabel = document.createElement('span');
    identifierLabel.className = 'ot-iconselector-identifier-label flex-grow-1';
    identifierLabel.textContent = item.identifier;
    preview.appendChild(identifierLabel);

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'btn btn-default btn-sm ot-iconselector-remove';
    removeButton.title = 'Entfernen';
    removeButton.innerHTML = '<typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>';
    preview.appendChild(removeButton);

    this.selectedContainer.appendChild(preview);
    this.bindRemoveButton();

    this.inputGroup.style.display = 'none';
    this.hideGrid();
    this.searchInput.value = '';

    this.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
  }

  bindRemoveButton() {
    const removeButton = this.selectedContainer.querySelector('.ot-iconselector-remove');
    if (!removeButton) {
      return;
    }

    removeButton.addEventListener('click', () => {
      this.hiddenInput.value = '';
      this.selectedContainer.innerHTML = '';
      this.inputGroup.style.display = 'flex';
      this.searchInput.value = '';
      this.searchInput.focus();
      this.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  toggleFavorite(identifier, starElement) {
    if (this.integratorFavorites.includes(identifier)) {
      return;
    }

    new AjaxRequest(this.favoriteUrl)
      .post({ identifier, group: this.favoriteGroup })
      .then(async (response) => {
        const data = await response.resolve();
        if (data.success) {
          this.userFavorites = data.favorites;
          if (starElement) {
            const isFavorite = this.userFavorites.includes(identifier);
            starElement.textContent = isFavorite ? '★' : '☆';
            starElement.style.opacity = isFavorite ? '1' : '0.3';
            starElement.title = isFavorite ? 'Favorit entfernen' : 'Als Favorit merken';
          }
        }
      });
  }

  setActiveIndex(newIndex) {
    const items = this.grid.querySelectorAll('.ot-iconselector-item');
    if (items.length === 0) {
      return;
    }

    if (this.activeIndex >= 0 && items[this.activeIndex]) {
      items[this.activeIndex].classList.remove('is-active');
      items[this.activeIndex].style.backgroundColor = '';
      items[this.activeIndex].style.borderColor = 'transparent';
    }

    this.activeIndex = Math.max(-1, Math.min(newIndex, items.length - 1));

    if (this.activeIndex >= 0 && items[this.activeIndex]) {
      const activeItem = items[this.activeIndex];
      activeItem.classList.add('is-active');
      activeItem.style.backgroundColor = 'var(--bs-tertiary-bg)';
      activeItem.style.borderColor = 'var(--bs-primary)';
      activeItem.scrollIntoView({ block: 'nearest' });
    }
  }
}

window.customElements.define('typo3-ot-icon-selector', IconSelectorElement);
