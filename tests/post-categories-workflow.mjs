/** Exercise category selection and creation through the actual editor component. */
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';

let filter, cursor = 0, slots = [], writes = [], creation;
const state = {
    type: 'post', selected: [2, 3],
    categories: [{ id: 1, name: 'Podujatia' }, { id: 2, name: 'Education' }, { id: 3, name: 'Community' }],
    links: { 'wp:action-assign-categories': [{}], 'wp:action-create-categories': [{}] }
};
const wp = {
    hooks: { addFilter: (hook, name, callback) => { filter = callback; } },
    element: {
        createElement: (type, props, ...children) => ({ type, props: props || {}, children }),
        useState: initial => {
            const index = cursor++;
            if (!(index in slots)) slots[index] = initial;
            return [slots[index], value => { slots[index] = value; }];
        }
    },
    data: {
        useSelect: callback => callback(store => store === 'core/editor' ? {
            getCurrentPost: () => ({ _links: state.links }),
            getCurrentPostType: () => state.type,
            getEditedPostAttribute: () => state.selected
        } : { getEntityRecords: () => state.categories }),
        useDispatch: store => store === 'core/editor' ? {
            editPost: value => { state.selected = Array.from(value.categories); writes.push(state.selected); }
        } : { saveEntityRecord: async (kind, taxonomy, value) => { creation = value; return { id: 4, name: value.name }; } }
    },
    components: Object.fromEntries(['SelectControl', 'TextControl', 'Button', 'Notice', 'Spinner'].map(name => [name, name])),
    i18n: { __: value => value }, htmlEntities: { decodeEntities: value => value }
};
vm.runInNewContext(readFileSync(new URL('../assets/js/post-categories.js', import.meta.url), 'utf8'), {
    window: { wp, camminoPostCategories: { eventCategoryId: 1 } }
});
const Original = () => {};
const Wrapped = filter(Original);
function render() {
    cursor = 0;
    const component = Wrapped({ slug: 'category' });
    return component.type(component.props);
}
function find(node, type) {
    if (node?.type === type) return node;
    for (const child of node?.children || []) {
        const match = find(child, type);
        if (match) return match;
    }
}
let checks = 0;
function check(callback) { callback(); checks++; }
let tree = render();
check(() => assert.equal(find(tree, 'SelectControl').props.value, '2'));
find(tree, 'SelectControl').props.onChange('3');
check(() => assert.deepEqual(state.selected, [3]));
state.selected = [1, 2];
tree = render();
find(tree, 'SelectControl').props.onChange('3');
check(() => assert.deepEqual(state.selected, [3, 1]));
check(() => assert.equal(find(tree, 'SelectControl').props.options.some(option => option.value === '1'), false));
find(tree, 'Button').props.onClick();
tree = render();
find(tree, 'TextControl').props.onChange('New category');
tree = render();
await find(tree, 'form').props.onSubmit({ preventDefault() {} });
check(() => assert.equal(creation.parent, 0));
check(() => assert.deepEqual(state.selected, [4, 1]));
check(() => assert.equal(find(render(), 'form'), undefined));
state.links = { 'wp:action-assign-categories': [{}] };
check(() => assert.equal(find(render(), 'Button'), undefined));
state.links = {};
check(() => assert.equal(render(), null));
check(() => assert.equal(Wrapped({ slug: 'post_tag' }).type, Original));
state.type = 'page';
check(() => assert.equal(Wrapped({ slug: 'category' }).type, Original));
console.log(`Passed ${checks} post category editor checks.`);
