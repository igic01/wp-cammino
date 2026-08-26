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
    const variableDialog = document.querySelector('[data-nstarter-variable-dialog]');
    const variableForm = document.querySelector('[data-nstarter-variable-form]');
    const variableTitle = document.querySelector('[data-nstarter-variable-title]');
    const variableLabel = document.querySelector('[data-nstarter-variable-label]');
    const variableInput = document.querySelector('[data-nstarter-variable-input]');
    const variableCancel = document.querySelector('[data-nstarter-variable-cancel]');

    if (!config || !frame) {
        return;
    }

    let mode = 'text';
    let busy = false;
    let dirty = false;
    let mediaFrame = null;
    let mediaTarget = null;
    let pendingVideoAttachment = null;
    let videoToolsLayer = null;
    let variableToolsLayer = null;
    let activeVariableSection = null;
    let transientState = new Map();

    function frameDocument() {
        return frame.contentDocument || frame.contentWindow.document;
    }

    function snapshotRoot() {
        return frameDocument().querySelector('[data-nstarter-snapshot-root]');
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

    function isInLiveSection(node) {
        const element = node && (node.nodeType === 1 ? node : node.parentElement);
        return Boolean(element && element.closest('[data-nstarter-live-section]'));
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
        if (!variableToolsLayer || mode === 'interaction') {
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

        if (mode === 'interaction' || !variableDialog) {
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
        const inputType = section.dataset.nstarterVariableType === 'text' ? 'text' : 'number';

        variableTitle.textContent = config.strings.editSectionVariable;
        variableLabel.textContent = label;
        variableInput.type = inputType;
        variableInput.value = section.dataset.nstarterVariableValue || '';
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

    function replaceTemplateTokens(html, index) {
        return html
            .replace(/\{\{index_padded\}\}/g, String(index).padStart(2, '0'))
            .replace(/\{\{index\}\}/g, String(index));
    }

    function resizeRepeatSection(section, nextCount) {
        const container = section.querySelector('[data-nstarter-variable-items]');
        const template = section.querySelector('template[data-nstarter-variable-template]');

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
                itemTemplate.innerHTML = replaceTemplateTokens(template.innerHTML, index);
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

        const type = section.dataset.nstarterVariableType === 'text' ? 'text' : 'number';
        const control = section.dataset.nstarterVariableControl === 'text' ? 'text' : 'repeat';
        let value = variableInput.value;

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
            const resized = type === 'number' ? resizeRepeatSection(section, value) : false;
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

    function applyMode(nextMode) {
        mode = nextMode;
        const doc = frameDocument();

        doc.designMode = mode === 'text' ? 'on' : 'off';
        doc.body.classList.remove('nstarter-mode-text', 'nstarter-mode-media', 'nstarter-mode-interaction');
        doc.body.classList.add('nstarter-mode-' + mode);
        lockLiveSections(doc);
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
        if (mode === 'interaction') {
            return;
        }

        event.stopImmediatePropagation();
        event.stopPropagation();

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
        if (!target || isInLiveSection(target)) {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        openMediaPicker(target);
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
    }

    async function save() {
        if (busy) {
            return;
        }

        setBusy(true);
        setStatus('Saving…');

        try {
            const data = await request('nstarter_save_snapshot', { html: serialiseSnapshot() });
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
        doc.addEventListener('beforeinput', protectLiveSection, true);
        doc.addEventListener('input', markDirty, true);
        doc.addEventListener('keydown', handleShortcut, true);
        doc.addEventListener('scroll', positionEditorTools, true);
        frame.contentWindow.addEventListener('resize', positionEditorTools);
        applyMode(mode);
    }

    modeSelect.addEventListener('change', function () {
        applyMode(modeSelect.value);
    });

    saveButton.addEventListener('click', save);
    regenerateButton.addEventListener('click', regenerate);
    panelToggle.addEventListener('click', togglePanel);
    videoForm.addEventListener('submit', applyVideoAttachment);
    videoCancel.addEventListener('click', cancelVideoOptions);
    videoDialog.addEventListener('cancel', function (event) {
        event.preventDefault();
        cancelVideoOptions();
    });
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
