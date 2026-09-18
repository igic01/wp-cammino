(function (wp) {
    'use strict';
    const { createElement: el, useState } = wp.element;
    const { useSelect, useDispatch } = wp.data;
    const { SelectControl, TextControl, Button, Notice, Spinner } = wp.components;
    const { __ } = wp.i18n;
    const query = { per_page: -1, orderby: 'name', order: 'asc', context: 'view' };
    const automaticId = Number(window.camminoPostCategories.eventCategoryId);

    function SingleCategorySelector() {
        const [name, setName] = useState('');
        const [adding, setAdding] = useState(false);
        const [showForm, setShowForm] = useState(false);
        const [error, setError] = useState('');
        const state = useSelect(function (select) {
            const editor = select('core/editor');
            const core = select('core');
            const post = editor.getCurrentPost();
            return {
                categories: core.getEntityRecords('taxonomy', 'category', query),
                selected: editor.getEditedPostAttribute('categories') || [],
                canAssign: !!post._links?.['wp:action-assign-categories'],
                canCreate: !!post._links?.['wp:action-create-categories']
            };
        }, []);
        const { editPost } = useDispatch('core/editor');
        const { saveEntityRecord, deleteEntityRecord } = useDispatch('core');
        function choose(id) {
            const selected = Number(id) ? [Number(id)] : [];
            if (automaticId && state.selected.includes(automaticId)) selected.push(automaticId);
            editPost({ categories: selected });
        }
        async function addCategory(event) {
            event.preventDefault();
            if (adding || !name.trim()) return;
            setAdding(true);
            setError('');
            try {
                const existing = state.categories.find(category => category.name.toLowerCase() === name.trim().toLowerCase());
                const category = existing || await saveEntityRecord('taxonomy', 'category', { name: name.trim(), parent: 0 }, { throwOnError: true });
                choose(category.id);
                setName('');
                setShowForm(false);
            } catch (failure) {
                setError(failure.message || __('Could not add the category.', 'cammino'));
            } finally {
                setAdding(false);
            }
        }
        async function removeCategory() {
            const category = state.categories.find(category => state.selected.includes(category.id) && category.id !== automaticId);
            if (adding || !category || !window.camminoPostCategories.canDeleteCategories || category.id === Number(window.camminoPostCategories.defaultCategoryId)) return;
            if (!window.confirm(wp.i18n.sprintf(__('Delete category "%s" permanently? It will be removed from the list and all posts using it. Posts will not be deleted.', 'cammino'), wp.htmlEntities.decodeEntities(category.name)))) return;
            setAdding(true);
            setError('');
            try {
                await deleteEntityRecord('taxonomy', 'category', category.id, { force: true }, { throwOnError: true });
                choose('');
            } catch (failure) {
                setError(failure.message || __('Could not delete the category.', 'cammino'));
            } finally {
                setAdding(false);
            }
        }
        if (!state.canAssign) return null;
        if (!state.categories) return el(Spinner);
        const selected = state.selected.find(id => id !== automaticId) || '';
        return el('div', null,
            error && el(Notice, { status: 'error', isDismissible: false }, error),
            el(SelectControl, {
                label: __('Category', 'cammino'), value: String(selected), disabled: adding,
                options: [{ label: __('Select a category', 'cammino'), value: '' }].concat(
                    state.categories.filter(category => category.id !== automaticId).map(category => ({ label: wp.htmlEntities.decodeEntities(category.name), value: String(category.id) }))
                ),
                onChange: choose
            }),
            selected && window.camminoPostCategories.canDeleteCategories && Number(selected) !== Number(window.camminoPostCategories.defaultCategoryId) && el(Button, { variant: 'secondary', isDestructive: true, disabled: adding, onClick: removeCategory }, __('Delete category', 'cammino')),
            state.canCreate && el(Button, { variant: 'link', disabled: adding, 'aria-expanded': showForm, onClick: () => setShowForm(!showForm) }, __('Add category', 'cammino')),
            showForm && el('form', { onSubmit: addCategory },
                el(TextControl, { label: __('New category name', 'cammino'), value: name, onChange: setName, required: true, disabled: adding }),
                el(Button, { type: 'submit', variant: 'secondary', disabled: adding || !name.trim() }, __('Add category', 'cammino'))
            )
        );
    }
    wp.hooks.addFilter('editor.PostTaxonomyType', 'cammino/single-category', function (Original) {
        return function (props) {
            const postType = useSelect(select => select('core/editor').getCurrentPostType(), []);
            return el(props.slug === 'category' && postType === 'post' ? SingleCategorySelector : Original, props);
        };
    });
})(window.wp);
