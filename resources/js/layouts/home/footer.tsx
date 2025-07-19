import { Activity } from "lucide-react";
import { Link } from "@inertiajs/react";

export default function Footer() {
    return (
        <footer className="py-12 px-4 border-t bg-background">
            <div className="max-w-7xl mx-auto">
                <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
              <div className="flex items-center gap-2 text-xl font-bold mb-4">
                <div className="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                  <Activity className="h-5 w-5 text-primary-foreground" />
                </div>
                <span>StatusPage</span>
              </div>
              <p className="text-muted-foreground">
                Modern status pages for modern teams. Keep your users informed with real-time updates.
              </p>
            </div>
            <div>
              <h3 className="font-semibold mb-4">Product</h3>
              <ul className="space-y-2 text-muted-foreground">
                <li><Link href="#" className="hover:text-foreground transition-colors">Features</Link></li>
                <li><Link href="#" className="hover:text-foreground transition-colors">Pricing</Link></li>
                <li><Link href="#" className="hover:text-foreground transition-colors">API</Link></li>
                <li><Link href="#" className="hover:text-foreground transition-colors">Documentation</Link></li>
              </ul>
            </div>
            <div>
              <h3 className="font-semibold mb-4">Company</h3>
              <ul className="space-y-2 text-muted-foreground">
                <li><Link href="#" className="hover:text-foreground transition-colors">About</Link></li>
                <li><Link href="#" className="hover:text-foreground transition-colors">Blog</Link></li>
                <li><Link href="#" className="hover:text-foreground transition-colors">Careers</Link></li>
                <li><Link href="#" className="hover:text-foreground transition-colors">Contact</Link></li>
              </ul>
            </div>
            <div>
              <h3 className="font-semibold mb-4">Support</h3>
              <ul className="space-y-2 text-muted-foreground">
                <li><Link href="#" className="hover:text-foreground transition-colors">Help Center</Link></li>
                <li><Link href="#" className="hover:text-foreground transition-colors">Status</Link></li>
                <li><Link href="#" className="hover:text-foreground transition-colors">Security</Link></li>
                <li><Link href="#" className="hover:text-foreground transition-colors">Privacy</Link></li>
              </ul>
            </div>
          </div>
          <div className="border-t mt-8 pt-8 text-center text-muted-foreground">
            <p>&copy; {new Date().getFullYear()} StatusPage. All rights reserved.</p>
          </div>
        </div>
      </footer>
    )
}