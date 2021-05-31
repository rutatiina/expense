import RecurringRoutes from './RecurringRouter'

const Index = () => import('./components/l-limitless-bs4/Index');
const Form = () => import('./components/l-limitless-bs4/Form');
const Show = () => import('./components/l-limitless-bs4/Show');
const SideBarLeft = () => import('./components/l-limitless-bs4/SideBarLeft');
const SideBarRight = () => import('./components/l-limitless-bs4/SideBarRight');

let routes = [

    {
        path: '/expenses',
        components: {
            default: Index,
            //'sidebar-left': ComponentSidebarLeft,
            //'sidebar-right': ComponentSidebarRight
        },
        meta: {
            title: 'Accounting :: Sales :: Expenses',
            metaTags: [
                {
                    name: 'description',
                    content: 'Expenses'
                },
                {
                    property: 'og:description',
                    content: 'Expenses'
                }
            ]
        }
    },
    {
        path: '/expenses/create',
        components: {
            default: Form,
            //'sidebar-left': ComponentSidebarLeft,
            //'sidebar-right': ComponentSidebarRight
        },
        meta: {
            title: 'Accounting :: Sales :: Expense :: Create',
            metaTags: [
                {
                    name: 'description',
                    content: 'Create Expense'
                },
                {
                    property: 'og:description',
                    content: 'Create Expense'
                }
            ]
        }
    },
    {
        path: '/expenses/:id',
        components: {
            default: Show,
            'sidebar-left': SideBarLeft,
            'sidebar-right': SideBarRight
        },
        meta: {
            title: 'Accounting :: Sales :: Expense',
            metaTags: [
                {
                    name: 'description',
                    content: 'Expense'
                },
                {
                    property: 'og:description',
                    content: 'Expense'
                }
            ]
        }
    },
    {
        path: '/expenses/:id/copy',
        components: {
            default: Form,
        },
        meta: {
            title: 'Accounting :: Sales :: Expense :: Copy',
            metaTags: [
                {
                    name: 'description',
                    content: 'Copy Expense'
                },
                {
                    property: 'og:description',
                    content: 'Copy Expense'
                }
            ]
        }
    },
    {
        path: '/expenses/:id/edit',
        components: {
            default: Form,
        },
        meta: {
            title: 'Accounting :: Sales :: Expense :: Edit',
            metaTags: [
                {
                    name: 'description',
                    content: 'Edit Expense'
                },
                {
                    property: 'og:description',
                    content: 'Edit Expense'
                }
            ]
        }
    }

]

routes = routes.concat(
    routes,
    RecurringRoutes
);

export default routes
