(function () {
    'use strict';

    const config = window.nstarterEditor;
    const frame = document.querySelector('[data-nstarter-frame]');
    const loading = document.querySelector('[data-nstarter-loading]');
    const status = document.querySelector('[data-nstarter-status]');
    const saveButton = document.querySelector('[data-nstarter-save]');
    const viewLink = document.querySelector('[data-nstarter-view]');
    const regenerateButton = document.querySelector('[data-nstarter-regenerate]');
    const modeSelect = document.querySelector('[data-nstarter-mode]');
    const editorPanel = document.querySelector('.nstarter-editor-panel');
    const panelToggle = document.querySelector('[data-nstarter-panel-toggle]');
    const videoDialog = document.querySelector('[data-nstarter-video-dialog]');
    const videoForm = document.querySelector('[data-nstarter-video-form]');
    const videoAutoplay = document.querySelector('[data-nstarter-video-autoplay]');
    const videoMuted = document.querySelector('[data-nstarter-video-muted]');
    const videoControls = document.querySelector('[data-nstarter-video-controls]');
    const videoCancel = document.querySelector('[data-nstarter-video-cancel]');
    const linkDialog = document.querySelector('[data-nstarter-link-dialog]');
    const linkForm = document.querySelector('[data-nstarter-link-form]');
    const linkInput = document.querySelector('[data-nstarter-link-input]');
    const linkCancel = document.querySelector('[data-nstarter-link-cancel]');
    const variableDialog = document.querySelector('[data-nstarter-variable-dialog]');
    const variableForm = document.querySelector('[data-nstarter-variable-form]');
    const variableTitle = document.querySelector('[data-nstarter-variable-title]');
    const variableLabel = document.querySelector('[data-nstarter-variable-label]');
    const variableInput = document.querySelector('[data-nstarter-variable-input]');
    const variableCancel = document.querySelector('[data-nstarter-variable-cancel]');
    const sectionOrderButton = document.querySelector('[data-nstarter-section-order]');
    const sectionOrderDialog = document.querySelector('[data-nstarter-section-order-dialog]');
    const sectionOrderForm = document.querySelector('[data-nstarter-section-order-form]');
    const sectionOrderList = document.querySelector('[data-nstarter-section-order-list]');
    const sectionOrderCancel = document.querySelector('[data-nstarter-section-order-cancel]');
    const contentEditorButton = document.querySelector('[data-nstarter-content-editor]');
    const contentDialog = document.querySelector('[data-nstarter-content-dialog]');
    const contentForm = document.querySelector('[data-nstarter-content-form]');
    const contentList = document.querySelector('[data-nstarter-content-list]');
    const contentClose = document.querySelector('[data-nstarter-content-close]');

    if (!config || !frame) {
        return;
    }

    let mode = 'text';
    let busy = false;
    let dirty = false;
    let mediaFrame = null;
    let mediaTarget = null;
    let linkTarget = null;
    let pendingVideoAttachment = null;
    let videoToolsLayer = null;
    let variableToolsLayer = null;
    let activeVariableSection = null;
    let orderedSections = [];
    let sectionOrderParent = null;
    let transientState = new Map();

    function frameDocument() {
        return frame.contentDocument || frame.contentWindow.document;
    }

    function snapshotRoot() {
        return frameDocument().querySelector('[data-nstarter-snapshot-root]');
    }

    function contentBuilder() {
        const root = snapshotRoot();
        return root && root.matches('[data-nstarter-content-builder]') ? root : null;
    }

    function contentItems() {
        const builder = contentBuilder();
        return builder
            ? Array.from(builder.children).filter(function (item) {
                return item.matches('[data-nstarter-content-item]');
            })
            : [];
    }

    function contentItemLabel(item, index) {
        const labels = {
            title: 'Title',
            paragraph: 'Paragraph',
            image: 'Image',
            content: 'Existing content'
        };
        const type = item.dataset.nstarterContentType || 'content';
        const preview = type === 'image'
            ? (item.querySelector('img') && item.querySelector('img').getAttribute('alt')) || ''
            : item.textContent.replace(/\s+/g, ' ').trim().slice(0, 48);

        return (labels[type] || 'Content') + ' ' + String(index + 1) + (preview ? ' — ' + preview : '');
    }

    function renderContentList() {
        if (!contentList) {
            return;
        }

        const items = contentItems();
        contentList.replaceChildren();

        items.forEach(function (item, index) {
            const row = document.createElement('li');
            const label = document.createElement('span');
            const moveUp = document.createElement('button');
            const moveDown = document.createElement('button');
            const remove = document.createElement('button');

            label.textContent = contentItemLabel(item, index);
            label.title = label.textContent;
            moveUp.type = moveDown.type = remove.type = 'button';
            moveUp.textContent = '↑';
            moveDown.textContent = '↓';
            remove.textContent = '×';
            moveUp.disabled = index === 0;
            moveDown.disabled = index === items.length - 1;
            moveUp.title = config.strings.contentItemUp;
            moveDown.title = config.strings.contentItemDown;
            remove.title = config.strings.contentItemDelete;

            moveUp.addEventListener('click', function () {
                item.parentElement.insertBefore(item, items[index - 1]);
                markDirty();
                renderContentList();
                refreshVariableTools();
            });
            moveDown.addEventListener('click', function () {
                items[index + 1].after(item);
                markDirty();
                renderContentList();
                refreshVariableTools();
            });
            remove.addEventListener('click', function () {
                if (!window.confirm(config.strings.confirmDeleteContent)) {
                    return;
                }
                item.remove();
                markDirty();
                renderContentList();
                refreshVariableTools();
                refreshVideoSettingsTools();
            });

            row.append(label, moveUp, moveDown, remove);
            contentList.appendChild(row);
        });
    }

    function openContentEditor() {
        if (!contentDialog || !contentBuilder()) {
            return;
        }
        renderContentList();
        contentDialog.showModal();
    }

    function closeContentEditor() {
        if (contentDialog && contentDialog.open) {
            contentDialog.close();
        }
    }

    function addContentItem(type) {
        const builder = contentBuilder();
        const template = builder && builder.querySelector('template[data-nstarter-content-template="' + type + '"]');
        const item = template && template.content.firstElementChild
            ? template.content.firstElementChild.cloneNode(true)
            : null;

        if (!builder || !item) {
            return;
        }

        const firstTemplate = Array.from(builder.children).find(function (child) {
            return child.matches('template[data-nstarter-content-template]');
        });
        builder.insertBefore(item, firstTemplate || null);
        markDirty();
        renderContentList();
        refreshVariableTools();
        refreshVideoSettingsTools();

        if (type === 'image') {
            const image = item.querySelector('img');
            closeContentEditor();
            if (image) {
                openMediaPicker(image);
            }
        }
    }

    function setStatus(message, state) {
        const label = status.querySelector('strong');
        if (label) {
            label.textContent = message;
        }
        status.classList.remove('is-dirty', 'is-success', 'is-error');
        if (state) {
            status.classList.add('is-' + state);
        }
    }

    function markDirty() {
        if (busy) {
            return;
        }
        dirty = true;
        setStatus(config.strings.unsaved, 'dirty');
        frame.contentWindow.requestAnimationFrame(positionEditorTools);
    }

    function togglePanel() {
        const collapsed = editorPanel.classList.toggle('is-collapsed');
        const icon = panelToggle.querySelector('span');

        panelToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        panelToggle.setAttribute(
            'aria-label',
            collapsed ? config.strings.expandControls : config.strings.collapseControls
        );

        if (icon) {
            icon.textContent = collapsed ? '⋯' : '−';
        }
    }

    function orderableSections() {
        const root = snapshotRoot();
        const main = root && (root.querySelector('main#main-content') || root.querySelector('main'));

        if (!main) {
            return { parent: null, sections: [] };
        }

        const sections = Array.from(main.children).filter(function (element) {
            return ['SECTION', 'DIV', 'ARTICLE'].includes(element.tagName)
                && !element.matches('header, footer, .site-header, .site-footer, [data-header]');
        });

        return { parent: main, sections: sections };
    }

    function sectionOrderLabel(section, index) {
        let heading = null;

        for (let level = 1; level <= 6; level += 1) {
            heading = section.querySelector('h' + level);
            if (heading) {
                break;
            }
        }

        if (heading) {
            const headingText = heading.textContent.replace(/\s+/g, ' ').trim();
            if (headingText) {
                return headingText;
            }
        }

        if (section.id) {
            return '#' + section.id;
        }

        if (section.classList.length) {
            return '.' + Array.from(section.classList).join('.');
        }

        return section.tagName.toLowerCase() + ' ' + String(index + 1);
    }

    function renderSectionOrderList() {
        if (!sectionOrderList) {
            return;
        }

        sectionOrderList.replaceChildren();

        if (!orderedSections.length) {
            const emptyItem = document.createElement('li');
            const emptyLabel = document.createElement('span');
            emptyLabel.textContent = config.strings.noOrderableSections;
            emptyItem.appendChild(emptyLabel);
            sectionOrderList.appendChild(emptyItem);
            return;
        }

        orderedSections.forEach(function (section, index) {
            const item = document.createElement('li');
            const label = document.createElement('span');
            const moveUp = document.createElement('button');
            const moveDown = document.createElement('button');

            label.textContent = sectionOrderLabel(section, index);
            label.title = label.textContent;

            moveUp.type = 'button';
            moveUp.textContent = '↑';
            moveUp.disabled = index === 0;
            moveUp.setAttribute('aria-label', config.strings.sectionOrderUp + ': ' + label.textContent);
            moveUp.title = config.strings.sectionOrderUp;
            moveUp.addEventListener('click', function () {
                const previous = orderedSections[index - 1];
                orderedSections[index - 1] = orderedSections[index];
                orderedSections[index] = previous;
                renderSectionOrderList();
            });

            moveDown.type = 'button';
            moveDown.textContent = '↓';
            moveDown.disabled = index === orderedSections.length - 1;
            moveDown.setAttribute('aria-label', config.strings.sectionOrderDown + ': ' + label.textContent);
            moveDown.title = config.strings.sectionOrderDown;
            moveDown.addEventListener('click', function () {
                const next = orderedSections[index + 1];
                orderedSections[index + 1] = orderedSections[index];
                orderedSections[index] = next;
                renderSectionOrderList();
            });

            item.append(label, moveUp, moveDown);
            sectionOrderList.appendChild(item);
        });
    }

    function openSectionOrder() {
        if (busy || !sectionOrderDialog) {
            return;
        }

        const result = orderableSections();
        sectionOrderParent = result.parent;
        orderedSections = result.sections;
        renderSectionOrderList();
        sectionOrderDialog.showModal();
    }

    function cancelSectionOrder() {
        orderedSections = [];
        sectionOrderParent = null;
        if (sectionOrderDialog && sectionOrderDialog.open) {
            sectionOrderDialog.close();
        }
    }

    function applySectionOrder(event) {
        event.preventDefault();

        if (!sectionOrderParent || !orderedSections.length) {
            cancelSectionOrder();
            return;
        }

        const currentSections = orderableSections().sections;
        const changed = currentSections.length === orderedSections.length
            && orderedSections.some(function (section, index) {
                return currentSections[index] !== section;
            });

        if (changed) {
            const slots = currentSections.map(function (section) {
                const slot = section.ownerDocument.createComment('nstarter-section-order');
                sectionOrderParent.insertBefore(slot, section);
                section.remove();
                return slot;
            });

            slots.forEach(function (slot, index) {
                slot.replaceWith(orderedSections[index]);
            });
            markDirty();
            refreshVideoSettingsTools();
            refreshVariableTools();
        }

        cancelSectionOrder();
    }

    function isInLiveSection(node) {
        const element = node && (node.nodeType === 1 ? node : node.parentElement);
        return Boolean(element && element.closest('[data-nstarter-live-section]'));
    }

    function isEditableLink(link) {
        const root = snapshotRoot();

        return Boolean(
            link
            && root
            && root.contains(link)
            && !isInLiveSection(link)
            && !link.matches('.skip-link')
            && !link.closest('.site-header, .site-footer')
        );
    }

    function isSafeLinkUrl(url) {
        return !/^[a-z][a-z\d+.-]*:/i.test(url) || /^(https?:|mailto:|tel:)/i.test(url);
    }

    function openLinkEditor(link) {
        if (!linkDialog || !linkInput || !isEditableLink(link)) {
            return;
        }

        linkTarget = link;
        linkInput.value = link.getAttribute('href') || '';
        linkInput.setCustomValidity('');
        linkDialog.showModal();
        linkInput.focus();
        linkInput.select();
    }

    function closeLinkEditor() {
        linkTarget = null;
        if (linkDialog && linkDialog.open) {
            linkDialog.close();
        }
    }

    function applyLink(event) {
        event.preventDefault();

        if (!linkTarget || !linkInput) {
            closeLinkEditor();
            return;
        }

        const url = linkInput.value.trim();
        if (!url || !isSafeLinkUrl(url)) {
            linkInput.setCustomValidity(config.strings.invalidLink);
            linkInput.reportValidity();
            return;
        }

        linkInput.setCustomValidity('');
        if (linkTarget.getAttribute('href') !== url) {
            linkTarget.setAttribute('href', url);
            markDirty();
        }
        closeLinkEditor();
    }

    function removeVideoSettingsTools() {
        if (videoToolsLayer) {
            videoToolsLayer.remove();
            videoToolsLayer = null;
        }
    }

    function positionVideoSettingsTools() {
        if (!videoToolsLayer || mode !== 'media') {
            return;
        }

        const previewWindow = frame.contentWindow;

        videoToolsLayer.querySelectorAll('[data-nstarter-video-settings]').forEach(function (button) {
            const video = button.nstarterVideoTarget;

            if (!video || !video.isConnected) {
                button.remove();
                return;
            }

            const rect = video.getBoundingClientRect();
            const isVisible = rect.width > 0
                && rect.height > 0
                && rect.bottom > 0
                && rect.right > 0
                && rect.top < previewWindow.innerHeight
                && rect.left < previewWindow.innerWidth;

            button.hidden = !isVisible;
            if (!isVisible) {
                return;
            }

            button.style.top = Math.max(4, rect.top + 8) + 'px';
            button.style.left = Math.max(4, Math.min(previewWindow.innerWidth - 36, rect.right - 36)) + 'px';
        });
    }

    function refreshVideoSettingsTools() {
        removeVideoSettingsTools();

        if (mode !== 'media') {
            return;
        }

        const doc = frameDocument();
        const root = snapshotRoot();
        if (!root) {
            return;
        }

        videoToolsLayer = doc.createElement('div');
        videoToolsLayer.className = 'nstarter-video-tools';
        videoToolsLayer.setAttribute('contenteditable', 'false');
        videoToolsLayer.setAttribute('aria-hidden', 'false');

        root.querySelectorAll('video').forEach(function (video) {
            if (isInLiveSection(video)) {
                return;
            }

            const button = doc.createElement('button');
            button.type = 'button';
            button.className = 'nstarter-video-settings-button';
            button.setAttribute('data-nstarter-video-settings', '');
            button.setAttribute('contenteditable', 'false');
            button.setAttribute('aria-label', config.strings.editVideoSettings);
            button.setAttribute('title', config.strings.editVideoSettings);
            button.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 17.25V20h2.75L17.81 8.94l-2.75-2.75L4 17.25Zm15.71-10.42a1 1 0 0 0 0-1.42l-1.12-1.12a1 1 0 0 0-1.42 0l-.88.88 2.75 2.75.67-.67Z"/></svg>';
            button.nstarterVideoTarget = video;
            videoToolsLayer.appendChild(button);
        });

        doc.body.appendChild(videoToolsLayer);
        positionVideoSettingsTools();
    }

    function removeVariableTools() {
        if (variableToolsLayer) {
            variableToolsLayer.remove();
            variableToolsLayer = null;
        }
    }

    function positionVariableTools() {
        if (!variableToolsLayer || mode === 'interaction' || mode === 'link') {
            return;
        }

        const previewWindow = frame.contentWindow;

        variableToolsLayer.querySelectorAll('[data-nstarter-variable-edit]').forEach(function (button) {
            const section = button.nstarterVariableSection;

            if (!section || !section.isConnected) {
                button.remove();
                return;
            }

            const rect = section.getBoundingClientRect();
            const isVisible = rect.width > 0
                && rect.height > 0
                && rect.bottom > 0
                && rect.right > 0
                && rect.top < previewWindow.innerHeight
                && rect.left < previewWindow.innerWidth;

            button.hidden = !isVisible;
            if (!isVisible) {
                return;
            }

            button.style.top = Math.max(4, rect.top + 10) + 'px';
            button.style.left = Math.max(4, Math.min(previewWindow.innerWidth - 42, rect.right - 42)) + 'px';
        });
    }

    function refreshVariableTools() {
        removeVariableTools();

        if (mode === 'interaction' || mode === 'link' || !variableDialog) {
            return;
        }

        const doc = frameDocument();
        const root = snapshotRoot();
        if (!root) {
            return;
        }

        variableToolsLayer = doc.createElement('div');
        variableToolsLayer.className = 'nstarter-variable-tools';
        variableToolsLayer.setAttribute('contenteditable', 'false');

        root.querySelectorAll('[data-nstarter-variable-section]').forEach(function (section) {
            if (isInLiveSection(section)) {
                return;
            }

            const label = section.dataset.nstarterVariableLabel || config.strings.editSectionVariable;
            const button = doc.createElement('button');
            button.type = 'button';
            button.className = 'nstarter-variable-edit-button';
            button.setAttribute('data-nstarter-variable-edit', '');
            button.setAttribute('contenteditable', 'false');
            button.setAttribute('aria-label', config.strings.editSectionVariable + ': ' + label);
            button.setAttribute('title', config.strings.editSectionVariable + ': ' + label);
            button.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 17.25V20h2.75L17.81 8.94l-2.75-2.75L4 17.25Zm15.71-10.42a1 1 0 0 0 0-1.42l-1.12-1.12a1 1 0 0 0-1.42 0l-.88.88 2.75 2.75.67-.67Z"/></svg>';
            button.nstarterVariableSection = section;
            variableToolsLayer.appendChild(button);
        });

        doc.body.appendChild(variableToolsLayer);
        positionVariableTools();
    }

    function positionEditorTools() {
        positionVideoSettingsTools();
        positionVariableTools();
    }

    function openVariableEditor(section) {
        if (!variableDialog || !variableInput || !section) {
            return;
        }

        activeVariableSection = section;
        const label = section.dataset.nstarterVariableLabel || config.strings.editSectionVariable;
        const variableType = section.dataset.nstarterVariableType || 'number';
        const inputType = variableType === 'text' ? 'text' : (variableType === 'boolean' ? 'checkbox' : 'number');

        variableTitle.textContent = config.strings.editSectionVariable;
        variableLabel.textContent = variableType === 'boolean' ? label + ' (áno / nie)' : label;
        variableInput.type = inputType;
        if (inputType === 'checkbox') {
            variableInput.checked = section.dataset.nstarterVariableValue === '1';
        } else {
            variableInput.value = section.dataset.nstarterVariableValue || '';
        }
        ['min', 'max', 'step'].forEach(function (name) {
            variableInput.removeAttribute(name);
            const value = section.dataset['nstarterVariable' + name.charAt(0).toUpperCase() + name.slice(1)];
            if (inputType === 'number' && value !== undefined) {
                variableInput.setAttribute(name, value);
            }
        });

        variableDialog.showModal();
        variableInput.focus();
        variableInput.select();
    }

    function cancelVariableEditor() {
        activeVariableSection = null;
        if (variableDialog && variableDialog.open) {
            variableDialog.close();
        }
    }

    function escapeRegExp(value) {
        return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function replaceTemplateTokens(html, index, section) {
        const token = section.dataset.nstarterVariableToken || 'index';
        const escapedToken = escapeRegExp(token);

        return html
            .replace(new RegExp('\\{\\{' + escapedToken + '_padded\\}\\}', 'g'), String(index).padStart(2, '0'))
            .replace(new RegExp('\\{\\{' + escapedToken + '\\}\\}', 'g'), String(index));
    }

    function resizeRepeatSection(section, nextCount) {
        const ownsVariableElement = function (element) {
            return element.closest('[data-nstarter-variable-section]') === section;
        };
        const container = Array.from(section.querySelectorAll('[data-nstarter-variable-items]'))
            .find(ownsVariableElement);
        const template = Array.from(section.querySelectorAll('template[data-nstarter-variable-template]'))
            .find(ownsVariableElement);

        if (!container) {
            return false;
        }

        const items = Array.from(container.children).filter(function (element) {
            return element.matches('[data-nstarter-variable-item]');
        });
        const currentCount = items.length;

        if (nextCount < currentCount) {
            const removeCount = currentCount - nextCount;
            const message = config.strings.confirmRemoveItems.replace('%d', String(removeCount));
            if (!window.confirm(message)) {
                return null;
            }

            items.slice(nextCount).forEach(function (item) {
                item.remove();
            });
        } else if (nextCount > currentCount) {
            if (!template) {
                return false;
            }

            for (let index = currentCount + 1; index <= nextCount; index += 1) {
                const itemTemplate = section.ownerDocument.createElement('template');
                itemTemplate.innerHTML = replaceTemplateTokens(template.innerHTML, index, section);
                container.appendChild(itemTemplate.content.cloneNode(true));
            }
        }

        return true;
    }

    function applyVariableValue(event) {
        event.preventDefault();

        const section = activeVariableSection;
        if (!section || !variableInput) {
            return;
        }

        const type = section.dataset.nstarterVariableType || 'number';
        const control = section.dataset.nstarterVariableControl === 'text' ? 'text' : 'repeat';
        let value = type === 'boolean' ? (variableInput.checked ? 1 : 0) : variableInput.value;

        if (type === 'number') {
            value = Number(value);
            if (!Number.isFinite(value)) {
                return;
            }

            value = Math.trunc(value);
            if (section.dataset.nstarterVariableMin !== undefined) {
                value = Math.max(value, Number(section.dataset.nstarterVariableMin));
            }
            if (section.dataset.nstarterVariableMax !== undefined) {
                value = Math.min(value, Number(section.dataset.nstarterVariableMax));
            }
        }

        if (control === 'repeat') {
            const resized = type === 'number' || type === 'boolean' ? resizeRepeatSection(section, value) : false;
            if (resized === null) {
                return;
            }
            if (!resized) {
                setStatus(config.strings.unsupportedVariable, 'error');
                return;
            }
        } else {
            const outputs = section.querySelectorAll('[data-nstarter-variable-output]');
            if (!outputs.length) {
                setStatus(config.strings.unsupportedVariable, 'error');
                return;
            }
            outputs.forEach(function (output) {
                const attribute = output.dataset.nstarterVariableOutputAttribute;
                if (attribute === 'href') {
                    const href = String(value).trim();
                    const unsafeProtocol = /^[a-z][a-z\d+.-]*:/i.test(href)
                        && !/^(https?:|mailto:|tel:)/i.test(href);
                    value = unsafeProtocol ? '#' : href;
                    output.setAttribute('href', value);
                    return;
                }

                output.textContent = String(value);
            });
        }

        section.dataset.nstarterVariableValue = String(value);
        activeVariableSection = null;
        variableDialog.close();
        markDirty();
        refreshVideoSettingsTools();
        refreshVariableTools();
    }

    function lockLiveSections(doc) {
        doc.querySelectorAll('[data-nstarter-live-section]').forEach(function (section) {
            section.setAttribute('contenteditable', 'false');
            section.setAttribute('aria-label', 'Live section — locked while editing');
        });
    }

    function lockPostChrome(doc) {
        if (!contentBuilder()) {
            return;
        }

        doc.querySelectorAll('.site-header, .article-hero, .article-cover, .article-share, .related-section, .site-footer').forEach(function (element) {
            element.setAttribute('contenteditable', 'false');
        });
    }

    function applyMode(nextMode) {
        mode = nextMode;
        const doc = frameDocument();

        doc.designMode = mode === 'text' ? 'on' : 'off';
        doc.body.classList.remove('nstarter-mode-text', 'nstarter-mode-media', 'nstarter-mode-link', 'nstarter-mode-interaction');
        doc.body.classList.add('nstarter-mode-' + mode);
        lockLiveSections(doc);
        lockPostChrome(doc);
        modeSelect.value = mode;
        refreshVideoSettingsTools();
        refreshVariableTools();
    }

    function protectLiveSection(event) {
        const doc = frameDocument();
        const selection = doc.getSelection();
        const selectionNode = selection && selection.anchorNode;

        if (isInLiveSection(event.target) || isInLiveSection(selectionNode)) {
            event.preventDefault();
            if (selection) {
                selection.removeAllRanges();
            }
        }
    }

    function copyPresentationAttributes(source, destination) {
        const mediaAttributes = new Set([
            'src', 'srcset', 'sizes', 'alt', 'poster', 'autoplay', 'muted',
            'controls', 'playsinline', 'preload', 'loop', 'loading', 'decoding',
            'data-attachment-id'
        ]);

        Array.from(source.attributes).forEach(function (attribute) {
            if (!mediaAttributes.has(attribute.name)) {
                destination.setAttribute(attribute.name, attribute.value);
            }
        });
    }

    function replaceMediaElement(replacement) {
        const replacementTarget = mediaTarget.tagName.toLowerCase() === 'img'
            && mediaTarget.parentElement
            && mediaTarget.parentElement.tagName.toLowerCase() === 'picture'
            ? mediaTarget.parentElement
            : mediaTarget;

        replacementTarget.replaceWith(replacement);
        mediaTarget = null;
        pendingVideoAttachment = null;
        markDirty();
        refreshVideoSettingsTools();
    }

    function useImageAttachment(attachment) {
        const image = mediaTarget.ownerDocument.createElement('img');

        copyPresentationAttributes(mediaTarget, image);
        image.setAttribute('src', attachment.url);
        image.setAttribute('data-attachment-id', attachment.id);
        image.setAttribute(
            'alt',
            attachment.alt || (mediaTarget.tagName.toLowerCase() === 'img' ? mediaTarget.getAttribute('alt') || '' : '')
        );
        replaceMediaElement(image);
    }

    function fillVideoOptions(video) {
        videoAutoplay.checked = Boolean(video && video.hasAttribute('autoplay'));
        videoMuted.checked = video
            ? video.hasAttribute('muted') || video.muted
            : true;
        videoControls.checked = video
            ? video.hasAttribute('controls')
            : true;
    }

    function showVideoOptions(attachment) {
        const replacingVideo = mediaTarget.tagName.toLowerCase() === 'video';

        pendingVideoAttachment = attachment;
        fillVideoOptions(replacingVideo ? mediaTarget : null);
        videoDialog.showModal();
    }

    function editCurrentVideoSettings(video) {
        mediaTarget = video;
        pendingVideoAttachment = null;
        fillVideoOptions(video);
        videoDialog.showModal();
    }

    function applyVideoSettings(video) {
        video.toggleAttribute('controls', videoControls.checked);
        video.toggleAttribute('autoplay', videoAutoplay.checked);
        video.toggleAttribute('muted', videoMuted.checked);
        video.controls = videoControls.checked;
        video.autoplay = videoAutoplay.checked;
        video.muted = videoMuted.checked;
    }

    function applyVideoAttachment(event) {
        event.preventDefault();

        if (!mediaTarget) {
            videoDialog.close();
            return;
        }

        if (!pendingVideoAttachment) {
            applyVideoSettings(mediaTarget);
            mediaTarget = null;
            videoDialog.close();
            markDirty();
            return;
        }

        const video = mediaTarget.ownerDocument.createElement('video');

        copyPresentationAttributes(mediaTarget, video);
        video.setAttribute('src', pendingVideoAttachment.url);
        video.setAttribute('data-attachment-id', pendingVideoAttachment.id);
        video.setAttribute('playsinline', '');
        video.setAttribute('preload', 'metadata');

        applyVideoSettings(video);

        videoDialog.close();
        replaceMediaElement(video);
    }

    function cancelVideoOptions() {
        pendingVideoAttachment = null;
        mediaTarget = null;
        videoDialog.close();
    }

    function openMediaPicker(target) {
        mediaTarget = target;

        mediaFrame = window.wp.media({
            title: config.strings.chooseMedia,
            button: { text: config.strings.useMedia },
            library: { type: ['image', 'video'] },
            multiple: false
        });

        mediaFrame.on('select', function () {
            const attachment = mediaFrame.state().get('selection').first().toJSON();

            if (!mediaTarget || !attachment || !attachment.url) {
                return;
            }

            const attachmentType = attachment.type || (attachment.mime || '').split('/')[0];

            if (attachmentType === 'image') {
                useImageAttachment(attachment);
            } else if (attachmentType === 'video') {
                showVideoOptions(attachment);
            } else {
                mediaTarget = null;
                setStatus(config.strings.unsupportedMedia, 'error');
            }
        });

        mediaFrame.open();
    }

    function stopPreviewInteraction(event) {
        if (mode === 'interaction') {
            return;
        }

        // Keep browser editing defaults, but never let page JavaScript receive
        // pointer events while Text or Media editing is active.
        event.stopImmediatePropagation();
        event.stopPropagation();
    }

    function handlePreviewClick(event) {
        const link = event.target.closest && event.target.closest('a[href]');

        // The editor iframe must always stay on the page being edited, even in
        // Interaction mode. The separate View action remains available.
        if (link) {
            event.preventDefault();
            event.stopImmediatePropagation();
            event.stopPropagation();
            if (mode === 'link') {
                openLinkEditor(link);
            }
            return;
        }

        if (mode === 'interaction') {
            return;
        }

        event.stopImmediatePropagation();
        event.stopPropagation();

        if (mode === 'link') {
            event.preventDefault();
            return;
        }

        const variableButton = event.target.closest
            && event.target.closest('[data-nstarter-variable-edit]');
        if (variableButton && variableButton.nstarterVariableSection) {
            event.preventDefault();
            openVariableEditor(variableButton.nstarterVariableSection);
            return;
        }

        if (mode === 'text') {
            const actionable = event.target.closest && event.target.closest(
                'a, button, input, select, textarea, label, summary, [onclick]'
            );

            // The preceding pointer event has already positioned the caret.
            // Prevent only activation/navigation defaults at click time.
            if (actionable) {
                event.preventDefault();
            }
            return;
        }

        const videoSettingsButton = event.target.closest
            && event.target.closest('[data-nstarter-video-settings]');
        if (videoSettingsButton && videoSettingsButton.nstarterVideoTarget) {
            event.preventDefault();
            editCurrentVideoSettings(videoSettingsButton.nstarterVideoTarget);
            return;
        }

        const target = event.target.closest && event.target.closest('img, video');
        const root = snapshotRoot();
        const isPostCover = target && target.matches('.article-cover__frame img');
        if (!target || isInLiveSection(target) || (contentBuilder() && !root.contains(target) && !isPostCover)) {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        openMediaPicker(target);
    }

    function preventPreviewFormNavigation(event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        event.stopPropagation();
    }

    function addTransientDefinition(definitions, element, type, names) {
        if (!element) {
            return;
        }

        if (!definitions.has(element)) {
            definitions.set(element, { attributes: new Set(), classes: new Set() });
        }

        names.forEach(function (name) {
            if (name) {
                definitions.get(element)[type].add(name);
            }
        });
    }

    function splitTransientNames(value) {
        return (value || '').split(/[\s,]+/).filter(Boolean);
    }

    function rememberTransientState(doc) {
        const definitions = new Map();
        const root = doc.querySelector('[data-nstarter-snapshot-root]');

        transientState = new Map();
        if (!root) {
            return;
        }

        root.querySelectorAll('[data-nstarter-transient-id]').forEach(function (element) {
            element.removeAttribute('data-nstarter-transient-id');
        });

        root.querySelectorAll('[aria-expanded]').forEach(function (element) {
            addTransientDefinition(definitions, element, 'attributes', ['aria-expanded']);

            // Accordion panels are normally the expanded button's next sibling.
            addTransientDefinition(definitions, element.nextElementSibling, 'attributes', ['hidden']);
        });

        root.querySelectorAll('[hidden]').forEach(function (element) {
            addTransientDefinition(definitions, element, 'attributes', ['hidden']);
        });

        root.querySelectorAll('details').forEach(function (element) {
            addTransientDefinition(definitions, element, 'attributes', ['open']);
        });

        root.querySelectorAll('[data-test-menu]').forEach(function (element) {
            addTransientDefinition(definitions, element, 'classes', ['is-open']);
        });

        root.querySelectorAll('[data-nstarter-transient-attributes]').forEach(function (element) {
            addTransientDefinition(
                definitions,
                element,
                'attributes',
                splitTransientNames(element.dataset.nstarterTransientAttributes)
            );
        });

        root.querySelectorAll('[data-nstarter-transient-class]').forEach(function (element) {
            addTransientDefinition(
                definitions,
                element,
                'classes',
                splitTransientNames(element.dataset.nstarterTransientClass)
            );
        });

        let index = 0;
        definitions.forEach(function (definition, element) {
            const id = String(index++);
            const state = { attributes: {}, classes: {} };

            definition.attributes.forEach(function (name) {
                state.attributes[name] = {
                    present: element.hasAttribute(name),
                    value: element.getAttribute(name)
                };
            });

            definition.classes.forEach(function (name) {
                state.classes[name] = element.classList.contains(name);
            });

            element.setAttribute('data-nstarter-transient-id', id);
            transientState.set(id, state);
        });
    }

    function restoreTransientState(copy) {
        copy.querySelectorAll('[data-nstarter-transient-id]').forEach(function (element) {
            const id = element.getAttribute('data-nstarter-transient-id');
            const state = transientState.get(id);

            if (state) {
                Object.keys(state.attributes).forEach(function (name) {
                    const attribute = state.attributes[name];
                    if (attribute.present) {
                        element.setAttribute(name, attribute.value === null ? '' : attribute.value);
                    } else {
                        element.removeAttribute(name);
                    }
                });

                Object.keys(state.classes).forEach(function (name) {
                    element.classList.toggle(name, state.classes[name]);
                });
            }

            element.removeAttribute('data-nstarter-transient-id');
        });
    }

    function serialiseSnapshot() {
        const root = snapshotRoot();
        if (!root) {
            throw new Error('Snapshot root was not found.');
        }

        const copy = root.cloneNode(true);
        restoreTransientState(copy);

        // Runtime live HTML never enters ACF; retain only the original marker.
        copy.querySelectorAll('[data-nstarter-live-section]').forEach(function (section) {
            section.replaceChildren();
            section.removeAttribute('contenteditable');
            section.removeAttribute('aria-label');
        });

        return copy.innerHTML;
    }

    async function request(action, extraData) {
        const body = new FormData();
        body.append('action', action);
        body.append('nonce', config.nonce);
        body.append('post_id', config.postId);

        Object.keys(extraData || {}).forEach(function (key) {
            body.append(key, extraData[key]);
        });

        const response = await fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: body
        });
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.data && data.data.message ? data.data.message : config.strings.error);
        }

        return data.data;
    }

    function setBusy(nextBusy) {
        busy = nextBusy;
        saveButton.disabled = nextBusy;
        regenerateButton.disabled = nextBusy;
        modeSelect.disabled = nextBusy;
        if (sectionOrderButton) {
            sectionOrderButton.disabled = nextBusy;
        }
        if (contentEditorButton) {
            contentEditorButton.disabled = nextBusy;
        }
    }

    async function save() {
        if (busy) {
            return;
        }

        setBusy(true);
        setStatus('Saving…');

        try {
            const coverImage = frameDocument().querySelector('.article-cover__frame img[data-attachment-id]');
            const data = await request('nstarter_save_snapshot', {
                html: serialiseSnapshot(),
                featured_image_id: coverImage ? coverImage.getAttribute('data-attachment-id') : ''
            });
            dirty = false;
            if (viewLink && data.viewUrl) {
                viewLink.href = data.viewUrl;
            }
            setStatus(data.message || config.strings.saved, 'success');
        } catch (error) {
            setStatus(error.message || config.strings.error, 'error');
        } finally {
            setBusy(false);
        }
    }

    async function regenerate() {
        if (busy || !window.confirm(config.strings.confirmRegenerate)) {
            return;
        }

        setBusy(true);
        setStatus('Regenerating…');

        try {
            const data = await request('nstarter_regenerate_snapshot');
            dirty = false;
            if (viewLink && data.viewUrl) {
                viewLink.href = data.viewUrl;
            }
            setStatus(data.message || config.strings.regenerated, 'success');
            loading.classList.remove('is-hidden');
            frame.src = config.previewUrl + (config.previewUrl.includes('?') ? '&' : '?') + 'nstarter_refresh=' + Date.now();
        } catch (error) {
            setStatus(error.message || config.strings.error, 'error');
        } finally {
            setBusy(false);
        }
    }

    function handleShortcut(event) {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
            event.preventDefault();
            save();
        }
    }

    function initialisePreview() {
        const doc = frameDocument();

        loading.classList.add('is-hidden');
        rememberTransientState(doc);
        doc.addEventListener('pointerdown', stopPreviewInteraction, true);
        doc.addEventListener('mousedown', stopPreviewInteraction, true);
        doc.addEventListener('auxclick', handlePreviewClick, true);
        doc.addEventListener('dblclick', handlePreviewClick, true);
        doc.addEventListener('click', handlePreviewClick, true);
        doc.addEventListener('submit', preventPreviewFormNavigation, true);
        doc.addEventListener('beforeinput', protectLiveSection, true);
        doc.addEventListener('input', markDirty, true);
        doc.addEventListener('keydown', handleShortcut, true);
        doc.addEventListener('scroll', positionEditorTools, true);
        frame.contentWindow.addEventListener('resize', positionEditorTools);
        applyMode(mode);
        if (contentEditorButton) {
            contentEditorButton.hidden = !contentBuilder();
        }
        if (sectionOrderButton && contentBuilder()) {
            sectionOrderButton.hidden = true;
        }
    }

    modeSelect.addEventListener('change', function () {
        applyMode(modeSelect.value);
    });

    saveButton.addEventListener('click', save);
    regenerateButton.addEventListener('click', regenerate);
    panelToggle.addEventListener('click', togglePanel);
    if (sectionOrderButton && sectionOrderDialog && sectionOrderForm && sectionOrderCancel) {
        sectionOrderButton.addEventListener('click', openSectionOrder);
        sectionOrderForm.addEventListener('submit', applySectionOrder);
        sectionOrderCancel.addEventListener('click', cancelSectionOrder);
        sectionOrderDialog.addEventListener('cancel', function (event) {
            event.preventDefault();
            cancelSectionOrder();
        });
    }
    if (contentEditorButton && contentDialog && contentForm && contentClose) {
        contentEditorButton.addEventListener('click', openContentEditor);
        contentClose.addEventListener('click', closeContentEditor);
        contentForm.addEventListener('submit', function (event) {
            event.preventDefault();
            closeContentEditor();
        });
        contentForm.querySelectorAll('[data-nstarter-content-add]').forEach(function (button) {
            button.addEventListener('click', function () {
                addContentItem(button.dataset.nstarterContentAdd);
            });
        });
        contentDialog.addEventListener('cancel', function (event) {
            event.preventDefault();
            closeContentEditor();
        });
    }
    videoForm.addEventListener('submit', applyVideoAttachment);
    videoCancel.addEventListener('click', cancelVideoOptions);
    videoDialog.addEventListener('cancel', function (event) {
        event.preventDefault();
        cancelVideoOptions();
    });
    if (linkDialog && linkForm && linkInput && linkCancel) {
        linkForm.addEventListener('submit', applyLink);
        linkCancel.addEventListener('click', closeLinkEditor);
        linkInput.addEventListener('input', function () {
            linkInput.setCustomValidity('');
        });
        linkDialog.addEventListener('cancel', function (event) {
            event.preventDefault();
            closeLinkEditor();
        });
    }
    variableForm.addEventListener('submit', applyVariableValue);
    variableCancel.addEventListener('click', cancelVariableEditor);
    variableDialog.addEventListener('cancel', function (event) {
        event.preventDefault();
        cancelVariableEditor();
    });
    frame.addEventListener('load', initialisePreview);
    document.addEventListener('keydown', handleShortcut);

    window.addEventListener('beforeunload', function (event) {
        if (!dirty) {
            return;
        }
        event.preventDefault();
        event.returnValue = '';
    });
}());
