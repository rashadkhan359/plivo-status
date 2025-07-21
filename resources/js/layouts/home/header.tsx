import { motion } from 'framer-motion';
import { Activity, LayoutDashboard, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

export default function Header({ auth }: { auth?: any }) {

    return (
        <header className="w-full py-6 px-4 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div className="max-w-7xl mx-auto flex items-center justify-between">
                <motion.div
                className="flex items-center gap-2 text-xl font-bold"
                initial={{ opacity: 0, x: -20 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ duration: 0.5 }}
            >
                <div className="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                    <Activity className="h-5 w-5 text-primary-foreground" />
                </div>
                <span className="text-primary">StatusPage</span>
            </motion.div>
            <motion.div
                className="flex gap-3"
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ duration: 0.5, delay: 0.1 }}
            >
                {auth?.user ? (
                    <>
                        <Link href={route('dashboard')}>
                            <Button variant="ghost">
                                <LayoutDashboard className="h-4 w-4 mr-2" />
                                Dashboard
                            </Button>
                        </Link>
                        <Link href={route('profile.edit')}>
                            <Button variant="outline">
                                <User className="h-4 w-4 mr-2" />
                                {auth.user.name}
                            </Button>
                        </Link>
                    </>
                ) : (
                    <>
                        <Link href={route('login')}>
                            <Button variant="ghost">Sign In</Button>
                        </Link>
                        <Link href={route('register')}>
                            <Button>Get Started Free</Button>
                        </Link>
                    </>
                )}
            </motion.div>
            </div>
        </header>
    )
}