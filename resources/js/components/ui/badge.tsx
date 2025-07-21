import * as React from "react"
import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"

import { cn } from "@/lib/utils"

const badgeVariants = cva(
  "inline-flex items-center justify-center rounded-full border px-3 py-1.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1.5 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-all duration-200 ease-in-out overflow-auto shadow-sm backdrop-blur-sm",
  {
    variants: {
      variant: {
        default:
          "border-primary/20 bg-primary/10 text-primary-foreground shadow-primary/20 [a&]:hover:bg-primary/20 [a&]:hover:shadow-primary/30",
        secondary:
          "border-secondary/20 bg-secondary/10 text-secondary-foreground shadow-secondary/20 [a&]:hover:bg-secondary/20 [a&]:hover:shadow-secondary/30",
        destructive:
          "border-red-500/20 bg-red-500/10 text-red-700 dark:text-red-400 shadow-red-500/20 [a&]:hover:bg-red-500/20 [a&]:hover:shadow-red-500/30 focus-visible:ring-red-500/20 dark:focus-visible:ring-red-500/40",
        outline:
          "border-border/50 bg-background/50 text-foreground shadow-border/20 [a&]:hover:bg-accent/50 [a&]:hover:text-accent-foreground [a&]:hover:shadow-accent/30",
        success:
          "border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 shadow-emerald-500/20 [a&]:hover:bg-emerald-500/20 [a&]:hover:shadow-emerald-500/30",
        warning:
          "border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-400 shadow-amber-500/20 [a&]:hover:bg-amber-500/20 [a&]:hover:shadow-amber-500/30",
        info:
          "border-blue-500/20 bg-blue-500/10 text-blue-700 dark:text-blue-400 shadow-blue-500/20 [a&]:hover:bg-blue-500/20 [a&]:hover:shadow-blue-500/30",
        muted:
          "border-muted/20 bg-muted/10 text-muted-foreground shadow-muted/20 [a&]:hover:bg-muted/20 [a&]:hover:shadow-muted/30",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
)

function Badge({
  className,
  variant,
  asChild = false,
  ...props
}: React.ComponentProps<"span"> &
  VariantProps<typeof badgeVariants> & { asChild?: boolean }) {
  const Comp = asChild ? Slot : "span"

  return (
    <Comp
      data-slot="badge"
      className={cn(badgeVariants({ variant }), className)}
      {...props}
    />
  )
}

export { Badge, badgeVariants }
