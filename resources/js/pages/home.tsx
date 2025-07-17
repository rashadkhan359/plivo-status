import { Head, Link } from '@inertiajs/react';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { useState } from 'react';

export default function Home() {
  const [orgSlug, setOrgSlug] = useState('');

  return (
    <div className="min-h-screen flex flex-col bg-background">
      <Head>
        <title>StatusPage - Modern Multi-Tenant Status Pages</title>
        <meta name="description" content="Create beautiful, real-time status pages for your organization. Multi-tenant, real-time, and easy to use." />
      </Head>
      <header className="w-full py-6 px-4 flex items-center justify-between max-w-6xl mx-auto">
        <div className="flex items-center gap-2 text-xl font-bold">
          <span className="text-primary">StatusPage</span>
        </div>
        <div className="flex gap-2">
          <Link href={route('login')}><Button variant="outline">Login</Button></Link>
          <Link href={route('register')}><Button>Get Started</Button></Link>
        </div>
      </header>
      <main className="flex-1 flex flex-col items-center justify-center px-4">
        <section className="text-center max-w-2xl mx-auto mb-12">
          <h1 className="text-4xl md:text-5xl font-extrabold mb-4 tracking-tight">Modern Multi-Tenant Status Pages</h1>
          <p className="text-lg text-muted-foreground mb-6">Create beautiful, real-time status pages for your organization. Manage incidents, maintenance, and keep your users informed with ease.</p>
          <div className="flex flex-col sm:flex-row gap-2 justify-center mb-4">
            <Link href={route('register')}><Button size="lg">Start Free</Button></Link>
            <Link href={route('login')}><Button size="lg" variant="outline">Login</Button></Link>
          </div>
        </section>
        <section className="w-full max-w-3xl mx-auto mb-12">
          <div className="bg-card rounded-xl shadow-lg p-6 flex flex-col md:flex-row gap-6 items-center">
            <div className="flex-1 text-left">
              <h2 className="text-2xl font-semibold mb-2">See a Live Demo</h2>
              <p className="text-muted-foreground mb-4">Preview a public status page and see real-time updates in action.</p>
              <Link href={route('status.public', { organization: 'demo-org' })}><Button>View Demo Status Page</Button></Link>
            </div>
            <div className="flex-1">
              <img src="/status-demo.png" alt="Status Page Demo" className="rounded-lg shadow-md w-full" />
            </div>
          </div>
        </section>
        <section className="w-full max-w-2xl mx-auto mb-12">
          <h2 className="text-xl font-semibold mb-2 text-center">Find a Public Status Page</h2>
          <form className="flex gap-2" onSubmit={e => { e.preventDefault(); if (orgSlug) window.location.href = route('status.public', { organization: orgSlug }); }}>
            <Input
              placeholder="Enter organization slug (e.g. acme)"
              value={orgSlug}
              onChange={e => setOrgSlug(e.target.value)}
              className="w-full"
            />
            <Button type="submit">Go</Button>
          </form>
        </section>
        <section className="w-full max-w-4xl mx-auto mb-12 grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="bg-muted rounded-lg p-6 text-center">
            <h3 className="font-semibold text-lg mb-2">Multi-Tenant</h3>
            <p className="text-muted-foreground">Each organization gets its own isolated status page and dashboard.</p>
          </div>
          <div className="bg-muted rounded-lg p-6 text-center">
            <h3 className="font-semibold text-lg mb-2">Real-Time Updates</h3>
            <p className="text-muted-foreground">Incidents and maintenance are updated instantly for all viewers.</p>
          </div>
          <div className="bg-muted rounded-lg p-6 text-center">
            <h3 className="font-semibold text-lg mb-2">Beautiful & Responsive</h3>
            <p className="text-muted-foreground">Modern, clean design that looks great on any device.</p>
          </div>
        </section>
      </main>
      <footer className="w-full py-6 px-4 text-center text-muted-foreground text-sm border-t mt-8">
        &copy; {new Date().getFullYear()} StatusPage. All rights reserved.
      </footer>
    </div>
  );
} 