(function (wp) {
    'use strict';
    const { createElement: el, useState } = wp.element;
    const { useSelect, useDispatch } = wp.data;
    const { CheckboxControl, TextControl, Button, Notice, Spinner } = wp.components;
    const { __ } = wp.i18n;
    const query = { per_page: -1, orderby: 'name', order: 'asc', context: 'view' };
    const automaticId = Number(window.camminoPostCategories.eventCategoryId);

    function CategorySelector() {
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
        const { saveEntityRecord } = useDispatch('core');
        function choose(id, checked) {
            const selected = checked
                ? [...state.selected, Number(id)]
                : state.selected.filter(selectedId => selectedId !== Number(id));
            editPost({ categories: [...new Set(selected)] });
        }
        async function addCategory(event) {
            event.preventDefault();
            if (adding || !name.trim()) return;
            setAdding(true);
            setError('');
            try {
                const existing = state.categories.find(category => category.name.toLowerCase() === name.trim().toLowerCase());
                const category = existing || await saveEntityRecord('taxonomy', 'category', { name: name.trim(), parent: 0 }, { throwOnError: true });
                choose(category.id, true);
                setName('');
                setShowForm(false);
            } catch (failure) {
                setError(failure.message || __('Could not add the category.', 'cammino'));
            } finally {
                setAdding(false);
            }
        }
        if (!state.canAssign) return null;
        if (!state.categories) return el(Spinner);
        return el('div', null,
            error && el(Notice, { status: 'error', isDismissible: false }, error),
            state.categories.filter(category => category.id !== automaticId).map(category => el(CheckboxControl, {
                key: category.id,
                label: wp.htmlEntities.decodeEntities(category.name),
                checked: state.selected.includes(category.id),
                disabled: adding,
                onChange: checked => choose(category.id, checked)
            })),
            state.canCreate && el(Button, { variant: 'link', disabled: adding, 'aria-expanded': showForm, onClick: () => setShowForm(!showForm) }, __('Add category', 'cammino')),
            showForm && el('form', { onSubmit: addCategory },
                el(TextControl, { label: __('New category name', 'cammino'), value: name, onChange: setName, required: true, disabled: adding }),
                el(Button, { type: 'submit', variant: 'secondary', disabled: adding || !name.trim() }, __('Add category', 'cammino'))
            )
        );
    }
    wp.hooks.addFilter('editor.PostTaxonomyType', 'cammino/categories', function (Original) {
        return function (props) {
            const postType = useSelect(select => select('core/editor').getCurrentPostType(), []);
            return el(props.slug === 'category' && postType === 'post' ? CategorySelector : Original, props);
        };
    });
})(window.wp);
