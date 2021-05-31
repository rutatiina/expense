
const Index = () => import('./components/l-limitless-bs4/recurring/Index');
const Form = () => import('./components/l-limitless-bs4/recurring/Form');
const Show = () => import('./components/l-limitless-bs4/recurring/Show');
const SideBarLeft = () => import('./components/l-limitless-bs4/recurring/SideBarLeft');
const SideBarRight = () => import('./components/l-limitless-bs4/recurring/SideBarRight');

const routes = [

    {
        path: '/recurring-expenses',
        components: {
            default: Index,
            //'sidebar-left': ComponentSidebarLeft,
            //'sidebar-right': ComponentSidebarRight
        },
        meta: {
            title: 'Accounting :: Sales :: Recurring Expenses',
            metaTags: [
                {
                    name: 'description',
                    content: 'Recurring Expenses'
                },
                {
                    property: 'og:description',
                    content: 'Recurring Expenses'
                }
            ]
        }
    },
    {
        path: '/recurring-expenses/create',
        components: {
            default: Form,
            //'sidebar-left': ComponentSidebarLeft,
            //'sidebar-right': ComponentSidebarRight
        },
        meta: {
            title: 'Accounting :: Sales :: Recurring Expense :: Create',
            metaTags: [
                {
                    name: 'description',
                    content: 'Create Recurring Expense'
                },
                {
                    property: 'og:description',
                    content: 'Create Recurring Expense'
                }
            ]
        }
    },
    {
        path: '/recurring-expenses/:id',
        components: {
            default: Show,
            'sidebar-left': SideBarLeft,
            'sidebar-right': SideBarRight
        },
        meta: {
            title: 'Accounting :: Sales :: Recurring Expense',
            metaTags: [
                {
                    name: 'description',
                    content: 'Recurring Expense'
                },
                {
                    property: 'og:description',
                    content: 'Recurring Expense'
                }
            ]
        }
    },
    {
        path: '/recurring-expenses/:id/copy',
        components: {
            default: Form,
        },
        meta: {
            title: 'Accounting :: Sales :: Recurring Expense :: Copy',
            metaTags: [
                {
                    name: 'description',
                    content: 'Copy Recurring Expense'
                },
                {
                    property: 'og:description',
                    content: 'Copy Recurring Expense'
                }
            ]
        }
    },
    {
        path: '/recurring-expenses/:id/edit',
        components: {
            default: Form,
        },
        meta: {
            title: 'Accounting :: Sales :: Recurring Expense :: Edit',
            metaTags: [
                {
                    name: 'description',
                    content: 'Edit Recurring Expense'
                },
                {
                    property: 'og:description',
                    content: 'Edit Recurring Expense'
                }
            ]
        }
    }

]

export default routes
