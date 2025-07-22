import { Head, Link } from '@inertiajs/react';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../components/ui/card';
import { Badge } from '../components/ui/badge';
import { 
    CheckCircle, 
    Clock, 
    Shield, 
    Users, 
    Zap, 
    Globe, 
    BarChart3, 
    Bell,
    ArrowRight,
    Star,
    Play,
    Mail,
    Phone,
    MessageSquare,
    TrendingUp,
    Activity,
    Eye,
    Lock,
    RefreshCw,
    LayoutDashboard
} from 'lucide-react';
import { motion } from 'framer-motion';
import { useState } from 'react';
import Header from '../layouts/home/header';
import Footer from '../layouts/home/footer';

export default function Home({ auth }: { auth: any }) {
  const [orgSlug, setOrgSlug] = useState('');

  // If user is authenticated, show dashboard redirect
  if (auth?.user) {
    return (
      <div className="min-h-screen bg-background">
        <Head>
          <title>Welcome Back - StatusPage</title>
        </Head>
        <Header auth={auth} />
        
        <section className="relative overflow-hidden py-20 px-4">
          <div className="max-w-4xl mx-auto text-center">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8 }}
            >
              <h1 className="text-4xl md:text-6xl font-bold mb-6">
                Welcome back, <span className="text-primary">{auth.user.name}</span>!
              </h1>
              <p className="text-xl text-muted-foreground mb-8 max-w-2xl mx-auto leading-relaxed">
                Ready to manage your status pages and monitor your services?
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Link href={route('dashboard')}>
                  <Button size="lg" className="text-lg px-8 py-6">
                    <LayoutDashboard className="mr-2 h-5 w-5" />
                    Go to Dashboard
                  </Button>
                </Link>
                <Link href={route('status.public', { organization: 'xkcd-robotics' })}>
                  <Button size="lg" variant="outline" className="text-lg px-8 py-6">
                    <Eye className="mr-2 h-5 w-5" />
                    View Demo
                  </Button>
                </Link>
              </div>
            </motion.div>
          </div>
        </section>
        
        <Footer />
      </div>
    );
  }

  const features = [
    {
      icon: <Globe className="h-6 w-6" />,
      title: "Multi-Tenant Architecture",
      description: "Each organization gets its own isolated status page and dashboard with complete data separation."
    },
    {
      icon: <Zap className="h-6 w-6" />,
      title: "Real-Time Updates",
      description: "Incidents and maintenance are updated instantly with WebSocket connections for live notifications."
    },
    {
      icon: <Shield className="h-6 w-6" />,
      title: "Role-Based Access",
      description: "Granular permissions system with admin, member, and viewer roles for secure team collaboration."
    },
    {
      icon: <BarChart3 className="h-6 w-6" />,
      title: "Advanced Analytics",
      description: "Comprehensive uptime metrics, incident tracking, and performance insights for your services."
    },
    {
      icon: <Bell className="h-6 w-6" />,
      title: "Smart Notifications",
      description: "Automated alerts via email, Slack, and webhooks when incidents occur or status changes."
    },
    {
      icon: <RefreshCw className="h-6 w-6" />,
      title: "Custom Branding",
      description: "White-label your status page with custom domains, logos, and brand colors."
    }
  ];

  const stats = [
    { label: "Organizations", value: "500+", icon: <Users className="h-4 w-4" /> },
    { label: "Services Monitored", value: "10K+", icon: <Activity className="h-4 w-4" /> },
    { label: "Uptime Tracked", value: "99.9%", icon: <TrendingUp className="h-4 w-4" /> },
    { label: "Incidents Resolved", value: "50K+", icon: <CheckCircle className="h-4 w-4" /> }
  ];

  const testimonials = [
    {
      name: "Sarah Chen",
      role: "DevOps Engineer",
      company: "TechFlow Inc",
      content: "StatusPage has transformed how we communicate with our users. The real-time updates and beautiful interface have significantly improved our customer satisfaction.",
      rating: 5
    },
    {
      name: "Marcus Rodriguez",
      role: "CTO",
      company: "CloudScale",
      content: "The multi-tenant architecture is exactly what we needed. We can manage multiple client status pages from a single dashboard.",
      rating: 5
    },
    {
      name: "Emily Watson",
      role: "Product Manager",
      company: "DataSync",
      content: "Setting up our status page was incredibly easy. The analytics and reporting features help us make data-driven decisions.",
      rating: 5
    }
  ];

  return (
    <div className="min-h-screen bg-background">
      <Head>
        <title>StatusPage - Modern Multi-Tenant Status Pages</title>
        <meta name="description" content="Create beautiful, real-time status pages for your organization. Multi-tenant, real-time, and easy to use." />
      </Head>

     <Header auth={auth} />

      {/* Hero Section */}
      <section className="relative overflow-hidden py-20 px-4">
        <div className="max-w-7xl mx-auto text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
          >
            <Badge variant="secondary" className="mb-6">
              <Zap className="h-3 w-3 mr-1" />
              Now with Real-Time Updates
            </Badge>
            <h1 className="text-5xl md:text-7xl font-bold mb-6 bg-gradient-to-r from-primary to-primary/60 bg-clip-text text-transparent">
              Modern Status Pages
              <br />
              <span className="text-foreground">for Modern Teams</span>
            </h1>
            <p className="text-xl text-muted-foreground mb-8 max-w-3xl mx-auto leading-relaxed">
              Create beautiful, real-time status pages that keep your users informed. 
              Multi-tenant architecture, advanced analytics, and seamless team collaboration.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center mb-12">
              <Link href={route('register')}>
                <Button size="lg" className="text-lg px-8 py-6">
                  Start Free Trial
                  <ArrowRight className="ml-2 h-5 w-5" />
                </Button>
              </Link>
              <Link href={route('status.public', { organization: 'xkcd-robotics' })}>
                <Button size="lg" variant="outline" className="text-lg px-8 py-6">
                  <Play className="mr-2 h-5 w-5" />
                  View Live Demo
                </Button>
              </Link>
            </div>
          </motion.div>

          {/* Stats */}
          <motion.div 
            className="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8, delay: 0.3 }}
          >
            {stats.map((stat, index) => (
              <div key={index} className="text-center">
                <div className="flex items-center justify-center gap-2 mb-2">
                  {stat.icon}
                  <span className="text-3xl font-bold text-primary">{stat.value}</span>
                </div>
                <p className="text-sm text-muted-foreground">{stat.label}</p>
              </div>
            ))}
          </motion.div>
        </div>

        {/* Background decoration */}
        <div className="absolute inset-0 -z-10">
          <div className="absolute top-1/4 left-1/4 w-72 h-72 bg-primary/10 rounded-full blur-3xl"></div>
          <div className="absolute bottom-1/4 right-1/4 w-96 h-96 bg-secondary/10 rounded-full blur-3xl"></div>
        </div>
      </section>

      {/* Features Section */}
      <section className="py-20 px-4 bg-muted/30">
        <div className="max-w-7xl mx-auto">
          <motion.div 
            className="text-center mb-16"
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
            viewport={{ once: true }}
          >
            <h2 className="text-4xl font-bold mb-4">Everything You Need</h2>
            <p className="text-xl text-muted-foreground max-w-2xl mx-auto">
              Powerful features designed to help you build and manage professional status pages
            </p>
          </motion.div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {features.map((feature, index) => (
              <motion.div
                key={index}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.6, delay: index * 0.1 }}
                viewport={{ once: true }}
              >
                <Card className="h-full hover:shadow-lg transition-all duration-300 border-0 bg-background/50 backdrop-blur">
                  <CardHeader>
                    <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4">
                      <div className="text-primary">{feature.icon}</div>
                    </div>
                    <CardTitle className="text-xl">{feature.title}</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <CardDescription className="text-base leading-relaxed">
                      {feature.description}
                    </CardDescription>
                  </CardContent>
                </Card>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Demo Section */}
      <section className="py-20 px-4">
        <div className="max-w-7xl mx-auto">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <motion.div
              initial={{ opacity: 0, x: -20 }}
              whileInView={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.8 }}
              viewport={{ once: true }}
            >
              <h2 className="text-4xl font-bold mb-6">See It In Action</h2>
              <p className="text-xl text-muted-foreground mb-8 leading-relaxed">
                Preview our live demo status page and experience real-time updates, 
                beautiful design, and seamless user experience.
              </p>
              <div className="space-y-4">
                <div className="flex items-center gap-3">
                  <CheckCircle className="h-5 w-5 text-green-500" />
                  <span>Real-time incident updates</span>
                </div>
                <div className="flex items-center gap-3">
                  <CheckCircle className="h-5 w-5 text-green-500" />
                  <span>Responsive design for all devices</span>
                </div>
                <div className="flex items-center gap-3">
                  <CheckCircle className="h-5 w-5 text-green-500" />
                  <span>Custom branding and themes</span>
                </div>
                <div className="flex items-center gap-3">
                  <CheckCircle className="h-5 w-5 text-green-500" />
                  <span>Uptime metrics and analytics</span>
                </div>
              </div>
              <div className="mt-8">
                <Link href={route('status.public', { organization: 'xkcd-robotics' })}>
                  <Button size="lg" className="text-lg">
                    <Eye className="mr-2 h-5 w-5" />
                    View Demo Status Page
                  </Button>
                </Link>
              </div>
            </motion.div>
            <motion.div
              initial={{ opacity: 0, x: 20 }}
              whileInView={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.8, delay: 0.2 }}
              viewport={{ once: true }}
              className="relative"
            >
              <div className="bg-gradient-to-br from-primary/20 to-secondary/20 rounded-2xl p-8 border">
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                      <span className="font-semibold">All Systems Operational</span>
                    </div>
                    <Badge variant="secondary">Live</Badge>
                  </div>
                  <div className="space-y-3">
                    <div className="flex items-center justify-between p-3 bg-background/50 rounded-lg">
                      <span>API Service</span>
                      <div className="flex items-center gap-2">
                        <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span className="text-sm text-muted-foreground">Operational</span>
                      </div>
                    </div>
                    <div className="flex items-center justify-between p-3 bg-background/50 rounded-lg">
                      <span>Database</span>
                      <div className="flex items-center gap-2">
                        <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span className="text-sm text-muted-foreground">Operational</span>
                      </div>
                    </div>
                    <div className="flex items-center justify-between p-3 bg-background/50 rounded-lg">
                      <span>CDN</span>
                      <div className="flex items-center gap-2">
                        <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span className="text-sm text-muted-foreground">Operational</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      {/* Testimonials */}
      <section className="py-20 px-4 bg-muted/30">
        <div className="max-w-7xl mx-auto">
          <motion.div 
            className="text-center mb-16"
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
            viewport={{ once: true }}
          >
            <h2 className="text-4xl font-bold mb-4">Trusted by Teams Worldwide</h2>
            <p className="text-xl text-muted-foreground">
              See what our customers have to say about StatusPage
            </p>
          </motion.div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {testimonials.map((testimonial, index) => (
              <motion.div
                key={index}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.6, delay: index * 0.1 }}
                viewport={{ once: true }}
              >
                <Card className="h-full">
                  <CardContent className="pt-6">
                    <div className="flex gap-1 mb-4">
                      {[...Array(testimonial.rating)].map((_, i) => (
                        <Star key={i} className="h-4 w-4 fill-yellow-400 text-yellow-400" />
                      ))}
                    </div>
                    <p className="text-muted-foreground mb-6 leading-relaxed">
                      "{testimonial.content}"
                    </p>
                    <div>
                      <p className="font-semibold">{testimonial.name}</p>
                      <p className="text-sm text-muted-foreground">
                        {testimonial.role} at {testimonial.company}
                      </p>
                    </div>
                  </CardContent>
                </Card>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Find Status Page Section */}
      <section className="py-20 px-4">
        <div className="max-w-4xl mx-auto text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
            viewport={{ once: true }}
          >
            <h2 className="text-4xl font-bold mb-6">Find a Public Status Page</h2>
            <p className="text-xl text-muted-foreground mb-8">
              Looking for a specific organization's status page? Enter their slug below.
            </p>
            <form 
              className="flex gap-4 max-w-md mx-auto" 
              onSubmit={e => { 
                e.preventDefault(); 
                if (orgSlug) window.location.href = route('status.public', { organization: orgSlug }); 
              }}
            >
              <Input
                placeholder="Enter organization slug (e.g. acme)"
                value={orgSlug}
                onChange={e => setOrgSlug(e.target.value)}
                className="flex-1"
              />
              <Button type="submit">Go</Button>
            </form>
          </motion.div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20 px-4 bg-primary text-primary-foreground">
        <div className="max-w-4xl mx-auto text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
            viewport={{ once: true }}
          >
            <h2 className="text-4xl font-bold mb-6">Ready to Get Started?</h2>
            <p className="text-xl mb-8 opacity-90">
              Join thousands of teams who trust StatusPage to keep their users informed.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link href={route('register')}>
                <Button size="lg" variant="secondary" className="text-lg px-8 py-6">
                  Start Free Trial
                  <ArrowRight className="ml-2 h-5 w-5" />
                </Button>
              </Link>
              <Link href={route('login')}>
                <Button size="lg" variant="outline" className="text-lg px-8 py-6 border-primary-foreground/20 text-primary-foreground hover:bg-primary-foreground/10">
                  Sign In
                </Button>
              </Link>
            </div>
          </motion.div>
        </div>
      </section>

      <Footer />
    </div>
  );
} 