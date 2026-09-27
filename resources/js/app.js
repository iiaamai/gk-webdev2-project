import Alpine from 'alpinejs';
import {
    createIcons,
    Activity,
    ChartColumn,
    ClipboardList,
    House,
    Inbox,
    LayoutDashboard,
    LogIn,
    LogOut,
    Mail,
    Menu,
    Package,
    Plus,
    Settings,
    Tags,
    Truck,
    UserPlus,
    Users,
    X,
} from 'lucide';

window.Alpine = Alpine;
Alpine.start();

createIcons({
    icons: {
        Activity,
        ChartColumn,
        ClipboardList,
        House,
        Inbox,
        LayoutDashboard,
        LogIn,
        LogOut,
        Mail,
        Menu,
        Package,
        Plus,
        Settings,
        Tags,
        Truck,
        UserPlus,
        Users,
        X,
    },
});
